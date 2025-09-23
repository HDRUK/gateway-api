<?php

namespace App\Jobs;

use Auditor;
use Config;
use Exception;
use App\Models\Collection;
use App\Models\DataAccessApplicationAnswer;
use App\Models\DataAccessApplicationReviewHasFile;
use App\Models\DataAccessTemplate;
use App\Models\DataAccessTemplateHasFile;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Dur;
use App\Models\DurHasDatasetVersion;
use App\Models\QuestionBank;
use App\Models\Team;
use App\Models\Upload;
use App\Imports\ImportDur;
use App\Imports\ImportStructuralMetadata;
use App\Http\Traits\LoggingContext;
use App\Http\Traits\MetadataOnboard;
use MetadataManagementController as MMC;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Maatwebsite\Excel\Facades\Excel;

class ScanFileUpload implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use MetadataOnboard;
    use LoggingContext;

    private int $uploadId = 0;
    private string $fileSystem = '';
    private string $entityFlag = '';
    private ?int $userId = null;
    private ?int $teamId = null;
    private ?string $inputSchema = null;
    private ?string $inputVersion = null;
    private ?string $outputSchema = null;
    private ?string $outputVersion = null;
    private bool $elasticIndexing = true;
    private ?int $datasetId = null;
    private ?int $collectionId = null;
    private ?int $applicationId = null;
    private ?int $questionId = null;
    private ?int $reviewId = null;
    private bool $isLocalOrTestEnv = false;

    private ?array $loggingContext = null;

    public $timeout = 180; // default timeout is 60

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $uploadId,
        string $fileSystem,
        string $entityFlag,
        ?int $userId,
        ?int $teamId,
        ?string $inputSchema,
        ?string $inputVersion,
        ?string $outputSchema,
        ?string $outputVersion,
        bool $elasticIndexing,
        ?int $datasetId,
        ?int $collectionId,
        ?int $applicationId,
        ?int $questionId,
        ?int $reviewId,
    ) {
        $this->uploadId = $uploadId;
        $this->fileSystem = $fileSystem;
        $this->entityFlag = strtolower($entityFlag);
        $this->userId = $userId;
        $this->teamId = $teamId;
        $this->inputSchema = $inputSchema;
        $this->inputVersion = $inputVersion;
        $this->outputSchema = $outputSchema;
        $this->outputVersion = $outputVersion;
        $this->elasticIndexing = $elasticIndexing;
        $this->datasetId = $datasetId;
        $this->collectionId = $collectionId;
        $this->applicationId = $applicationId;
        $this->questionId = $questionId;
        $this->reviewId = $reviewId;
        $this->isLocalOrTestEnv = (strtoupper(config('app.env')) === 'TESTING' || strtoupper(config('app.env')) === 'LOCAL');

        $this->loggingContext = $this->getLoggingContext(\request());
        $this->loggingContext['method_name'] = class_basename($this);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {

        $upload = Upload::findOrFail($this->uploadId);
        $filePath = $upload->file_location;

        try {
            $body = [
                'file' => (string)$filePath,
                'storage' => (string)$this->fileSystem
            ];

            \Log::info('Malware scan initiated', $this->loggingContext);

            $response = Http::withHeaders($this->loggingContext)
            ->withBody(
                json_encode([
                    'file' => $filePath,
                    'storage' => $this->fileSystem,
                ]),
                'application/json',
            )->withBasicAuth(
                env('CLAMAV_BASIC_AUTH_USERNAME', 'user1'), // bite me
                env('CLAMAV_BASIC_AUTH_PASSWORD', 'passw0rd'),
            )->post(env('CLAMAV_API_URL', 'http://clamav:3001') . '/scan_file');

            $response = $response->json();

            \Log::info('Malware scan response', $response);

            $isError = isset($response['isError']) ? $response['isError'] : false;

            if ($isError === true) {
                throw new Exception($response['error']);
            }

            $isInfected = $response['isInfected'];

            \Log::info('Malware scan completed', $this->loggingContext);

            // Check if the file is infected
            if ($isInfected) {
                $upload->update([
                    'status' => 'FAILED',
                    'error' => $response['viruses']
                ]);
                Storage::disk($this->fileSystem . '.unscanned')
                    ->delete($upload->file_location);

                \Log::info('Uploaded file failed malware scan', $this->loggingContext);

                Auditor::log([
                    'action_type' => 'SCAN',
                    'action_service' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Uploaded file failed malware scan',
                ]);
            } else {

                \Log::info('Uploaded file passed malware scan', $this->loggingContext);

                $loc = $upload->file_location;
                $content = Storage::disk($this->fileSystem . '.unscanned')->get($loc);

                Storage::disk($this->fileSystem . '.scanned')->put($loc, $content);
                Storage::disk($this->fileSystem . '.unscanned')->delete($loc);

                \Log::info('Uploaded file moved to safe scanned storage', $this->loggingContext);

                switch ($this->entityFlag) {
                    case 'dur-from-upload':
                        $this->createDurFromFile($loc, $upload);
                        break;
                    case 'dataset-from-upload':
                        $this->createDatasetFromFile($loc, $upload);
                        break;
                    case 'structural-metadata-upload':
                        $this->attachStructuralMetadata($loc, $upload);
                        break;
                    case 'teams-media':
                        $this->uploadTeamMedia($loc, $upload, $this->teamId);
                        break;
                    case 'collections-media':
                        $this->uploadCollectionMedia($loc, $upload, $this->collectionId);
                        break;
                    case 'dar-application-upload':
                        $this->darApplicationUpload($loc, $upload);
                        break;
                    case 'dar-template-upload':
                        $this->darTemplateUpload($loc, $upload);
                        break;
                    case 'dar-review-upload':
                        $this->darReviewUpload($loc, $upload);
                        break;
                }

                \Log::info('Uploaded file passed malware scan and processed', $this->loggingContext);

                Auditor::log([
                    'action_type' => 'SCAN',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Uploaded file passed malware scan and processed',
                ]);
            }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $filePath,
                'error' => $e->getMessage()
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            \Log::info('ScanFileUpload failed', $this->loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    private function createDurFromFile(string $loc, Upload $upload): void
    {
        try {
            $data = [
                'user_id' => $this->userId,
                'team_id' => $this->teamId,
            ];
            $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);

            $import = new ImportDur($data);

            if ($this->isLocalOrTestEnv) {
                Excel::import($import, $path);
            } else {
                Excel::import($import, $path, $this->fileSystem . '.scanned');
            }

            $durIds = $import->durImport->durIds;

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'dur',
                'entity_id' => $durIds[0] ?? null,
            ]);

            \Log::info('Post processing ' . $this->entityFlag . ' completed', $this->loggingContext);

        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function linkDatasets(int $durId): void
    {
        $dur = Dur::findOrFail($durId);
        $nonGatewayDatasets = $dur['non_gateway_datasets'];
        $unmatched = array();
        foreach ($nonGatewayDatasets as $d) {
            // Try to match on url
            if (str_contains($d, env('GATEWAY_URL'))) {
                $exploded = explode('/', $d);
                $datasetId = (int) end($exploded);
                $dataset = Dataset::where('id', $datasetId)->first();
                if ($dataset) {
                    $dvID = $dataset->latestVersionID($datasetId);
                    DurHasDatasetVersion::create([
                        'dur_id' => $durId,
                        'dataset_version_id' => $dvID
                    ]);
                    continue;
                }
            }

            // Try to string match on dataset titles
            // BES 30/10/24: skip this attempt if running on an sqlite DB_CONNECTION
            // because JSON_UNQUOTE does not exist in sqlite
            // and the alternative of grabbing and searching all the metadata is computationally infeasible
            if (env('DB_CONNECTION') !== 'sqlite') {
                $dCleaned = trim($d);
                $datasetVersion = DatasetVersion::whereRaw(
                    'LOWER(short_title) LIKE ?',
                    ['%' . strtolower($dCleaned) . '%']
                )->latest('version')->first();
                if ($datasetVersion) {
                    DurHasDatasetVersion::create([
                        'dur_id' => $durId,
                        'dataset_version_id' => $datasetVersion->id
                    ]);
                    continue;
                }
            }

            // If no match above, assume $d is a non gateway dataset
            $unmatched[] = $d;
        }

        $dur->update([
            'non_gateway_datasets' => $unmatched
        ]);
    }

    private function createDatasetFromFile(string $loc, Upload $upload): void
    {
        try {
            $team = Team::findOrFail($this->teamId)->toArray();

            $content = Storage::disk($this->fileSystem . '.scanned')->get($loc);
            $metadata = json_decode($content, true);


            if (!array_key_exists('identifier', $metadata['summary']['dataCustodian'])) {
                throw new Exception('Invalid metadata: dataCustodian identifier missing.');
            }

            $dataCustodianIdentifier = $metadata['summary']['dataCustodian']['identifier'];

            if ($dataCustodianIdentifier !== $team['pid']) {
                throw new Exception("Mismatch: dataCustodian identifier ($dataCustodianIdentifier) does not match team PID ({$team['pid']}).");
            }
            $input = [
                'metadata' => ['metadata' => json_decode($content)],
                'status' => 'DRAFT',
                'create_origin' => 'MANUAL',
                'user_id' => $this->userId,
                'team_id' => $this->teamId,
            ];

            $defineInputSchema = $this->inputSchema ? $this->inputSchema : Config::get('form_hydration.schema.model');
            $defineInputVersion = $this->inputVersion ? $this->inputVersion : Config::get('form_hydration.schema.latest_version');

            $metadataResult = $this->metadataOnboard(
                $input,
                $team,
                $defineInputSchema,
                $defineInputVersion,
                $this->elasticIndexing
            );

            if ($metadataResult['translated']) {
                $upload->update([
                    'status' => 'PROCESSED',
                    'file_location' => $loc,
                    'entity_type' => 'dataset',
                    'entity_id' => $metadataResult['dataset_id']
                ]);

                \Log::info('Post processing ' . $this->entityFlag . ' completed', $this->loggingContext);

                Auditor::log([
                    'user_id' => $this->userId,
                    'team_id' => $this->teamId,
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'Dataset ' . $metadataResult['dataset_id'] .
                        ' with version ' . $metadataResult['version_id'] . ' created',
                ]);
            } else {
                $upload->update([
                    'status' => 'FAILED',
                    'file_location' => $loc,
                    'error' => $metadataResult['response']
                ]);

                \Log::info(
                    'Post processing ' . $this->entityFlag . ' failed with ' . $metadataResult['response'],
                    $this->loggingContext
                );
            }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function attachStructuralMetadata(string $loc, Upload $upload)
    {
        try {
            $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);

            $import = [];

            if ($this->isLocalOrTestEnv) {
                $import = Excel::toArray(new ImportStructuralMetadata(), $path);
            } else {
                $import = Excel::toArray(new ImportStructuralMetadata(), $path, $this->fileSystem . '.scanned');
            }

            $structuralMetadata = array();
            foreach ($import[0] as $row) {
                if (!$this->allNull($row)) {
                    $structuralMetadata[] = [
                        'name' => $row['table_name'],
                        'description' => $row['table_description'],
                        'columns' => array([
                            'name' => $row['column_name'],
                            'description' => $row['column_description'],
                            'dataType' => $row['data_type'],
                            'sensitive' => (bool) $row['sensitive'],
                        ])
                    ];
                }
            }

            // Check structural metadata against schema using traser
            $metadataInput = [
                'metadata' => [
                    'structuralMetadata' => $structuralMetadata
                ]
            ];
            // LS - Traser can't deal with part structural metadata, thus we have to rely on the overall
            // validation later on. For now, removing this.
            //
            // Leaving these commented out for now, to discuss later

            // // Default to using the latest GWDM for input and output
            // $inputSchema = $this->inputSchema ? $this->inputSchema : Config::get('metadata.GWDM.name');
            // $inputVersion = $this->inputVersion ? $this->inputVersion : Config::get('metadata.GWDM.version');
            // $outputSchema = $this->outputSchema ? $this->outputSchema : Config::get('metadata.GWDM.name');
            // $outputVersion = $this->outputVersion ? $this->outputVersion : Config::get('metadata.GWDM.version');
            // $result = MMC::translateDataModelType(
            //     json_encode($metadataInput),
            //     $outputSchema,
            //     $outputVersion,
            //     $inputSchema,
            //     $inputVersion,
            //     true,
            //     true,
            //     "structuralMetadata",
            // );

            // if ($result['wasTranslated']) {
            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'structural_metadata',
                'structural_metadata' => json_encode($metadataInput['metadata']['structuralMetadata']),
            ]);
            \Log::info('Post processing ' . $this->entityFlag . ' completed', $this->loggingContext);
            // } else {
            //     $upload->update([
            //         'status' => 'FAILED',
            //         'file_location' => $loc,
            //         'error' => $result['traser_message'],
            //     ]);
            // }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function uploadTeamMedia(string $loc, Upload $upload, int $teamId): void
    {
        $team = Team::findOrFail($teamId);
        $this->uploadMedia($loc, $upload, $team, 'teams', 'team_logo');
    }


    private function uploadCollectionMedia(string $loc, Upload $upload, int $collectionId): void
    {
        $collection = Collection::findOrFail($collectionId);
        $this->uploadMedia($loc, $upload, $collection, 'collections', 'image_link');
    }

    private function darApplicationUpload(string $loc, Upload $upload): void
    {
        try {
            // add file to dar application answers table
            $questionVersion = QuestionBank::where('id', $this->questionId)
                ->first()
                ->latestVersion()
                ->first();
            if (!$questionVersion) {
                throw new Exception('No question version found for question with id ' . $this->questionId);
            }
            $componentType = $questionVersion->question_json['field']['component'];

            if ($componentType === 'FileUpload') {
                DataAccessApplicationAnswer::updateOrCreate(
                    [
                        'application_id' => $this->applicationId,
                        'question_id' => $this->questionId,
                    ],
                    [
                        'application_id' => $this->applicationId,
                        'question_id' => $this->questionId,
                        'contributor_id' => $this->userId,
                        'answer' => [
                            'value' => [
                                'filename' => $upload->filename,
                                'id' => $upload->id,
                            ]
                        ]
                    ]
                );
            } elseif ($componentType === 'FileUploadMultiple') {
                $answer = DataAccessApplicationAnswer::where([
                    'application_id' => $this->applicationId,
                    'question_id' => $this->questionId,
                ])->first();
                if ($answer) {
                    $thisFile = [[
                        'filename' => $upload->filename,
                        'id' => $upload->id,
                    ]];
                    $value = array_merge(
                        $answer['answer']['value'],
                        $thisFile,
                    );
                    $answer->update([
                        'answer' => ['value' => $value]
                    ]);
                } else {
                    DataAccessApplicationAnswer::create([
                        'application_id' => $this->applicationId,
                        'question_id' => $this->questionId,
                        'contributor_id' => $this->userId,
                        'answer' => [
                            'value' => [[
                                'filename' => $upload->filename,
                                'id' => $upload->id,
                            ]]
                        ]
                    ]);
                }
            }

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'dataAccessApplication',
                'entity_id' => $this->applicationId,
                'question_id' => $this->questionId,
            ]);

            \Log::info('Post processing ' . $this->entityFlag . ' completed', $this->loggingContext);

        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function darTemplateUpload(string $loc, Upload $upload): void
    {
        try {
            // add file based dar template
            $template = DataAccessTemplate::create([
                'team_id' => $this->teamId,
                'user_id' => $this->userId,
                'published' => true,
                'locked' => false,
                'template_type' => 'DOCUMENT',
            ]);

            DataAccessTemplateHasFile::create([
                'template_id' => $template->id,
                'upload_id' => $upload->id,
            ]);

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'dataAccessTemplate',
                'entity_id' => $template->id,
            ]);

            \Log::info('Post processing ' . $this->entityFlag . ' completed', $this->loggingContext);

        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function darReviewUpload(string $loc, Upload $upload): void
    {
        try {
            DataAccessApplicationReviewHasFile::create([
                'review_id' => $this->reviewId,
                'upload_id' => $upload->id,
            ]);

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'dataAccessReview',
                'entity_id' => $this->reviewId,
            ]);

            \Log::info('Post processing ' . $this->entityFlag . ' completed', $this->loggingContext);

        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);

            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function uploadMedia(string $loc, Upload $upload, $entity, string $entityName, string $imageCol): void
    {
        try {
            $entityId = $entity->id;
            $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);

            $imageValid = $this->validateImage($path);

            if ($imageValid['result']) {
                $content = Storage::disk($this->fileSystem . '.scanned')->get($loc);
                Storage::disk($this->fileSystem . '.media')->put('/' . $entityName . '/' . $loc, $content);
                $newPath = '/' . $entityName . '/' . $loc;

                $entity->update([
                    $imageCol => $newPath
                ]);

                $upload->update([
                    'status' => 'PROCESSED',
                    'file_location' => $newPath,
                    'entity_type' => $entityName,
                    'entity_id' => $entityId,
                    'error' => $imageValid['message']
                ]);

                \Log::info('Post processing ' . $this->entityFlag . ' completed', $this->loggingContext);
            } else {
                $upload->update([
                    'status' => 'FAILED',
                    'file_location' => $loc,
                    'error' => $imageValid['message']
                ]);
                \Log::info(
                    'Post processing ' . $this->entityFlag . ' failed with ' . $imageValid['message'],
                    $this->loggingContext
                );
            }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);
            \Log::info(
                'Post processing ' . $this->entityFlag . ' failed with ' . $e->getMessage(),
                $this->loggingContext
            );
            throw new Exception($e->getMessage());
        }
    }

    private function validateImage($path): mixed
    {
        $result = true;
        $message = null;

        $manager = new ImageManager(Driver::class);
        if ($this->fileSystem === 'gcs') {
            $image = $manager->read(
                Storage::disk($this->fileSystem . '.scanned')->get($path)
            );
        } else {
            $image = $manager->read($path);
        }
        $size = $image->size();
        $width = $size->width();
        $height = $size->height();
        $ratio = $size->aspectRatio();

        if ($width < Config::get('image_uploads.width') || $height < Config::get('image_uploads.height')) {
            $result = false;
            $message = "The image you have uploaded does not meet the minimum 
                resolution requirements. Please ensure your image is at least 600px 
                wide, by 300px high. Please either select another image or alternatively 
                click \"Use default image\" and a default background image will be applied.";
        } elseif ($ratio < Config::get('image_uploads.aspect')) {
            $message = "The image you have uploaded does not meet the recommended 
                aspect ratio of 2:1. This may lead to your image not being displayed 
                as intended. Please either select another image or alternatively click 
                \"Use this image\" and we will proceed with the image you have provided.";
        }

        return [
            'result' => $result,
            'message' => $message
        ];
    }

    private function allNull(array $array): bool
    {
        foreach ($array as $a) {
            if (!is_null($a)) {
                return false;
            }
        }
        return true;
    }

    public function tags(): array
    {
        return [
            'file_scanner',
            'upload_id:' . $this->uploadId,
        ];
    }
}
