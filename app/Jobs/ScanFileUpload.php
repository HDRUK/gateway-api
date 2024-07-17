<?php

namespace App\Jobs;

use Auditor;
use Exception;

use App\Models\Upload;
use App\Imports\ImportDur;

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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private int $uploadId = 0;
    private string $fileSystem = '';
    private string $entityFlag = '';
    private int | null $userId = null;
    private int | null $teamId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $uploadId, 
        string $fileSystem, 
        string $entityFlag, 
        int | null $userId, 
        int | null $teamId
    )
    {
        $this->uploadId = $uploadId;
        $this->fileSystem = $fileSystem;
        $this->entityFlag = $entityFlag;
        $this->userId = $userId;
        $this->teamId = $teamId;
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
                $entityId = $this->createDurFromFile($loc);
            }

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc
            ]);

            Auditor::log([
                'action_type' => 'SCAN',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Uploaded file passed malware scan",
            ]);
        }
    }

    private function createDurFromFile(string $loc): int 
    {
        $data = [
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
        ];
        $path = Storage::disk($this->fileSystem . '.scanned')->path($loc);

        $import = new ImportDur($data);
        Excel::import($import, $path);

        $durId = $import->durImport->durId;

        return $durId;
    }

}