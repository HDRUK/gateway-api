<?php

namespace App\Jobs;

use Auditor;
use Exception;

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
        int | null $datasetId
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
            Storage::disk($this->fileSystem . '.unscanned')->delete($loc);

            if ($this->entityFlag === 'dur-from-upload') {
                $this->createDurFromFile($loc, $upload);
            } else if ($this->entityFlag === 'dataset-from-upload') {
                $this->createDatasetFromFile($loc, $upload);
            } else if ($this->entityFlag === 'structural-metadata-upload') {
                $this->attachStructuralMetadata($loc, $upload, $this->datasetId);
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