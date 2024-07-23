<?php

namespace App\Jobs;

use Auditor;
use Exception;

use App\Models\Collection;
use App\Models\Dataset;
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
use Intervention\Image\Decoders\FilePathImageDecoder;
use Intervention\Image\Drivers\Imagick\Driver;

use Maatwebsite\Excel\Facades\Excel;

class ScanFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, MetadataOnboard;
    
    private int $uploadId = 0;
    private string $fileSystem = '';
    private string $entityFlag = '';
    private int | null $userId = null;
    private int | null $teamId = null;
    private string | null $inputSchema = null;
    private string | null $inputVersion = null;
    private bool $elasticIndexing = true;
    private int | null $datasetId = null;
    private int | null $collectionId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $uploadId, 
        string $fileSystem, 
        string $entityFlag, 
        int | null $userId, 
        int | null $teamId,
        string | null $inputSchema,
        string | null $inputVersion,
        bool $elasticIndexing,
        int | null $datasetId,
        int | null $collectionId
    )
    {
        $this->uploadId = $uploadId;
        $this->fileSystem = $fileSystem;
        $this->entityFlag = $entityFlag;
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
        $upload = Upload::findOrFail($this->uploadId);
        $filePath = $upload->file_location;

        $body = [
            'file' => (string) $filePath, 
            'storage' => (string) $this->fileSystem
        ];
        $url = env('CLAMAV_API_URL', 'http://clamav:3001') . '/scan_file';
        
        $response = Http::post(
            env('CLAMAV_API_URL', 'http://clamav:3001') . '/scan_file',
            ['file' => $filePath, 'storage' => $this->fileSystem]
        );
        $isInfected = $response['isInfected'];

        // Check if the file is infected
        if ($isInfected) {
            $upload->update([
                'status' => 'FAILED',
                'error' => $response['viruses']
            ]);
            Storage::disk($this->fileSystem . '.unscanned')
                ->delete($upload->file_location);
            
            Auditor::log([
                'action_type' => 'SCAN',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Uploaded file failed malware scan",
            ]);
        } else {
            $loc = $upload->file_location;

            $content = Storage::disk($this->fileSystem . '.unscanned')->get($loc);
            Storage::disk($this->fileSystem . '.scanned')->put($loc, $content);
            // Storage::disk($this->fileSystem . '.unscanned')->delete($loc);

            if ($this->entityFlag === 'dur-from-upload') {
                $this->createDurFromFile($loc, $upload);
            } else if ($this->entityFlag === 'dataset-from-upload') {
                $this->createDatasetFromFile($loc, $upload);
            } else if ($this->entityFlag === 'structural-metadata-upload') {
                $this->attachStructuralMetadata($loc, $upload, $this->datasetId);
            } else if ($this->entityFlag === 'teams-media') {
                $this->uploadTeamMedia($loc, $upload, $this->teamId);
            } else if ($this->entityFlag === 'collections-media') {
                $this->uploadCollectionMedia($loc, $upload, $this->collectionId);
            }

            Auditor::log([
                'action_type' => 'SCAN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Uploaded file passed malware scan and processed",
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
            $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);

            $import = new ImportDur($data);
            Excel::import($import, $path);

            $durId = $import->durImport->durId;

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'dur',
                'entity_id' => $durId
            ]);
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
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
                $input, $team, $this->inputSchema, $this->inputVersion, $this->elasticIndexing
            );

            if ($metadataResult['translated']) {
                $upload->update([
                    'status' => 'PROCESSED',
                    'file_location' => $loc,
                    'entity_type' => 'dataset',
                    'entity_id' => $metadataResult['dataset_id']
                ]);

                Auditor::log([
                    'user_id' => $this->userId,
                    'team_id' => $this->teamId,
                    'action_type' => 'CREATE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Dataset " . $metadataResult['dataset_id'] . " with version " . $metadataResult['version_id'] . " created",
                ]);
            } else {
                $upload->update([
                    'status' => 'FAILED',
                    'file_location' => $loc,
                    'error' => $metadataResult['response']
                ]);
            }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);
            throw new Exception($e->getMessage());
        }
    }

    private function attachStructuralMetadata(string $loc, Upload $upload, int $datasetId)
    {
        try {
            $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);
            $dataset = Dataset::findOrFail($datasetId);
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
                            'sensitive' => $row['sensitive']
                        ])
                    ];
                }
            }

            $version = $dataset->latestVersion();
            $metadata = $version->metadata;
            $metadata['metadata']['structuralMetadata'] = $structuralMetadata;
            $version->update([
                'metadata' => $metadata
            ]);

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc,
                'entity_type' => 'dataset',
                'entity_id' => $datasetId
            ]);
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);
            throw new Exception($e->getMessage());
        }
    }

    private function uploadTeamMedia(string $loc, Upload $upload, int $teamId): void
    {
        try {
            $team = Team::findOrFail($teamId);
            $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);

            $imageValid = $this->validateImage($path);

            if ($imageValid['result']) {
                $content = Storage::disk($this->fileSystem . '.scanned')->get($loc);
                Storage::disk($this->fileSystem . '.media')->put('/teams' . '/' . $loc, $content);
                $newPath = Storage::disk($this->fileSystem . '.media')->path('/teams' . '/' . $loc);

                $team->update([
                    'team_logo' => $newPath
                ]);

                $upload->update([
                    'status' => 'PROCESSED',
                    'file_location' => $newPath,
                    'entity_type' => 'team',
                    'entity_id' => $teamId,
                    'error' => $imageValid['message']
                ]);
            } else {
                $upload->update([
                    'status' => 'FAILED',
                    'file_location' => $loc,
                    'error' => $imageValid['message']
                ]);
            }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);
            throw new Exception($e->getMessage());
        }
    }


    private function uploadCollectionMedia(string $loc, Upload $upload, int $collectionId): void
    {
        try {
            $collection = Collection::findOrFail($collectionId);
            $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);

            $imageValid = $this->validateImage($path);

            if ($imageValid['result']) {
                $content = Storage::disk($this->fileSystem . '.scanned')->get($loc);
                Storage::disk($this->fileSystem . '.media')->put('/collections' . '/' . $loc, $content);
                $newPath = Storage::disk($this->fileSystem . '.media')->path('/collections' . '/' . $loc);

                $collection->update([
                    'image_link' => $newPath
                ]);

                $upload->update([
                    'status' => 'PROCESSED',
                    'file_location' => $newPath,
                    'entity_type' => 'collection',
                    'entity_id' => $collectionId,
                    'error' => $imageValid['message']
                ]);
            } else {
                $upload->update([
                    'status' => 'FAILED',
                    'file_location' => $loc,
                    'error' => $imageValid['message']
                ]);
            }
        } catch (Exception $e) {
            // Record exception in uploads table
            $upload->update([
                'status' => 'FAILED',
                'file_location' => $loc,
                'error' => $e->getMessage()
            ]);
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

        if ($width < 600 || $height < 300) {
            $result = false;
            $message = "The image you have uploaded does not meet the minimum 
                resolution requirements. Please ensure your image is at least 600px 
                wide, by 300px high. Please either select another image or alternatively 
                click \"Use default image\" and a default background image will be applied.";
        } else if ($ratio < 2) {
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