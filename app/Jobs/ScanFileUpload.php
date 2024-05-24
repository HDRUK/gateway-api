<?php

namespace App\Jobs;

use Auditor;
use Exception;

use App\Models\Upload;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ScanFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private int $uploadId = 0;
    private string $fileSystem = '';

    /**
     * Create a new job instance.
     */
    public function __construct(int $uploadId, string $fileSystem)
    {
        $this->uploadId = $uploadId;
        $this->fileSystem = $fileSystem;
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

            $upload->update([
                'status' => 'PROCESSED',
                'file_location' => $loc
            ]);

            Auditor::log([
                'action_type' => 'SCAN',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Uploaded file passed malware scan",
            ]);
        }
    }

}