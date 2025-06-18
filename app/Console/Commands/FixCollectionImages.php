<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Collection;

class FixCollectionImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-collection-images {--dryRun : Perform a dry run without updating the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes collection images.';


    private $csvData = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/collection_images_update_and_fix.csv');
        $dryRun = $this->option('dryRun');
        $csvData = $this->csvData;

        $progressbar = $this->output->createProgressBar(count($csvData));
        $progressbar->start();

        //add these new images
        foreach ($csvData as $item) {
            $collectionMongoId = $item['PID'];

            $imagePath = (is_null($item['New File Name']) || empty($item['New File Name'])) ? null : '/collections/' . $item['New File Name'];
            $collection = Collection::where("mongo_object_id", $collectionMongoId);

            if ($dryRun) {
                $col = $collection->first();
                if (!$col) {
                    $this->error("Cannot find collection mongo id = {$collectionMongoId}");
                } else {
                    $this->info("Team ID: {$col->id}, Old Logo: {$col->image_link}, New Logo: {$imagePath}");
                }
            } else {
                $collection->update(['image_link' => $imagePath]);
            }
            $progressbar->advance();
        }

        if ($dryRun) {
            $nbad = Collection::where("image_link", "not like", "/collection%")->count();
            $this->info("There are {$nbad} collections still, these images will be removed");
        } else {
            //if any images still dont start with /collection i.e. saved in our GCP bucket - remove them!
            Collection::where("image_link", "not like", "/collection%")->update(['image_link' => null]);
        }

    }

    private function readMigrationFile(string $migrationFile): void
    {
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[trim($headers[$key], "\xEF\xBB\xBF")] = $value ?: '';
            }

            $this->csvData[] = $item;
        }

        fclose($file);
    }
}
