<?php

namespace App\Jobs;

use Auditor;
use CloudLogger;
use Config;
use Exception;

use App\Models\Collection;
use App\Models\Team;
use App\Models\Upload;
use App\Imports\ImportDur;
use App\Imports\ImportStructuralMetadata;

use App\Http\Traits\MetadataOnboard;

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

    private int $uploadId = 0;
    private string $fileSystem = '';
    private string $entityFlag = '';
    private ?int $userId = null;
    private ?int $teamId = null;
    private ?string $inputSchema = null;
    private ?string $inputVersion = null;
    private bool $elasticIndexing = true;
    private ?int $datasetId = null;
    private ?int $collectionId = null;

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
        bool $elasticIndexing,
        ?int $datasetId,
        ?int $collectionId
    ) {
        $this->uploadId = $uploadId;
        $this->fileSystem = $fileSystem;
        $this->entityFlag = strtolower($entityFlag);
        $this->userId = $userId;
        $this->teamId = $teamId;
        $this->inputSchema = $inputSchema;
        $this->inputVersion = $inputVersion;
        $this->elasticIndexing = $elasticIndexing;
        $this->datasetId = $datasetId;
        $this->collectionId = $collectionId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {

        try {

            $upload = Upload::findOrFail($this->uploadId);
            $filePath = $upload->file_location;
    
            $body = [
                'file' => (string)$filePath,
                'storage' => (string)$this->fileSystem
            ];

            CloudLogger::write('Malware scan initiated');
    
            $response = Http::timeout(60)->post(
                env('CLAMAV_API_URL', 'http://clamav:3001') . '/scan_file',
                [
                    'file' => $filePath,
                    'storage' => $this->fileSystem,
                ]
            );
            $isError = $response['isError'];

            if ($isError === true) {
                throw new Exception($response['error']);
            }

            $isInfected = $response['isInfected'];
            
    
            CloudLogger::write('Malware scan completed');
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

            throw new Exception($e->getMessage());
        }
       

        // Check if the file is infected
        if ($isInfected) {
            $upload->update([
                'status' => 'FAILED',
                'error' => $response['viruses']
            ]);
            Storage::disk($this->fileSystem . '.unscanned')
                ->delete($upload->file_location);

            CloudLogger::write([
                'action_type' => 'SCAN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Uploaded file failed malware scan',
            ]);

            Auditor::log([
                'action_type' => 'SCAN',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Uploaded file failed malware scan',
            ]);
        } else {

            CloudLogger::write('Uploaded file passed malware scan');

            $loc = $upload->file_location;
            
            $content = Storage::disk($this->fileSystem . '.unscanned')->get($loc);

            Storage::disk($this->fileSystem . '.scanned')->put($loc, $content);
            Storage::disk($this->fileSystem . '.unscanned')->delete($loc);

            CloudLogger::write('Uploaded file moved to safe scanned storage');

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
            }

            CloudLogger::write([
                'action_type' => 'SCAN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Uploaded file passed malware scan and processed',
            ]);

            Auditor::log([
                'action_type' => 'SCAN',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Uploaded file passed malware scan and processed',
            ]);
        }
    }

    private function createDurFromFile(string $loc, Upload $upload): void
    {
        try {
            $data = [
                'user_id' => $this->userId,
                'team_id' => $this->teamId,
            ];

            $import = new ImportDur($data);

            Excel::import($import, $loc, 'gcs.scanned');

            $durId = $import->durImport->durId;

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'dur',
                'entity_id' => $durId
            ]);

            CloudLogger::write('Post processing ' . $this->entityFlag . ' completed');

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

    private function createDatasetFromFile(string $loc, Upload $upload): void
    {
        try {
            $team = Team::findOrFail($this->teamId)->toArray();

            $content = Storage::disk($this->fileSystem . '.scanned')->get($loc);
            $input = [
                'metadata' => ['metadata' => json_decode($content)],
                'status' => 'DRAFT',
                'create_origin' => 'MANUAL',
                'user_id' => $this->userId,
                'team_id' => $this->teamId,
            ];
            $metadataResult = $this->metadataOnboard(
                $input,
                $team,
                $this->inputSchema,
                $this->inputVersion,
                $this->elasticIndexing
            );

            if ($metadataResult['translated']) {
                $upload->update([
                    'status' => 'PROCESSED',
                    'file_location' => $loc,
                    'entity_type' => 'dataset',
                    'entity_id' => $metadataResult['dataset_id']
                ]);

                CloudLogger::write('Post processing ' . $this->entityFlag . ' completed');

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

                CloudLogger::write('Post processing ' . $this->entityFlag . ' failed with ' . $metadataResult['response']);
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
            $import = Excel::toArray(new ImportStructuralMetadata(), $path);

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
                            'sensitive' => $row['sensitive'],
                        ])
                    ];
                }
            }

            // Check structural metadata against schema using traser
            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'structural_metadata',
                'structural_metadata' => $structuralMetadata
            ]);
            CloudLogger::write('Post processing ' . $this->entityFlag . ' completed');
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
                CloudLogger::write('Post processing ' . $this->entityFlag . ' completed');
            } else {
                $upload->update([
                    'status' => 'FAILED',
                    'file_location' => $loc,
                    'error' => $imageValid['message']
                ]);
                CloudLogger::write(
                    'Post processing ' . $this->entityFlag . ' failed with ' . $imageValid['message']
                );
            }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);
            CloudLogger::write('Post processing ' . $this->entityFlag . ' failed with ' . $e->getMessage());
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

}
