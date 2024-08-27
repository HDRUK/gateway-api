<?php

namespace App\Console\Commands;

use Exception;

use App\Models\Upload;

use App\Models\Collection;

use Illuminate\Console\Command;


class UploadImagesPostMigrationProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:upload-images-post-migration-process';

    /**
     * The file of migration mappings translated to CSV array
     *
     * @var array
     */
    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to post-process migrated uploaded images from mk1 mongo db.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/collection_images.csv');

        // Traverse the CSV data and update migrations accordingly
        foreach ($this->csvData as $csv) {
            try {
                $pID = $csv['PID'];
                $action = $csv['action'];
                $fileLoc = $csv['file_loc'];
                $newFileName = $csv['New File Name'];
                
                if ($action === 'null') {
                    $fileLoc = null;
                }

                $upload = Upload::updateOrCreate([
                'id' => (int) $pID,
                'filename' => $newFileName,
                'file_location' => $fileLoc,
                'user_id' => 1,
                'status' => 'PROCESSED'
                ]);

                $upload->save();

                $collection = Collection::where('mongo_id', '=', $pID)->first();

                if ($collection) {
                    $collection::update([
                        'image_link' => $fileLoc
                    ]);
                    $collection->save();
                }

                echo 'completed post-process of migration for uploaded image ' . $upload->id . PHP_EOL;

            } catch (Exception $e) {
                echo 'unable to process ' . $pID . ' because ' . $e->getMessage() . "\n";
            }
        }
    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[$headers[$key]] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }

    /**
     * Get the CSV data for testing purposes.
     *
     * @return array
     */
    public function getCsvData(): array
    {
        return $this->csvData;
    }

}
