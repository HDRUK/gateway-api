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

    public function __construct()
    {
        parent::__construct();
        $this->readMigrationFile(storage_path() . '/migration_files/collection_images_update_and_fix.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dryRun');
        $csvData = $this->csvData;

        $progressbar = $this->output->createProgressBar(count($csvData));
        $progressbar->start();

        foreach ($csvData as $item) {
            $collectionMongoId = $item['PID'];

            $imagePath = (is_null($item['New File Name']) || empty($item['New File Name'])) ? null : '/collections/' . $item['New File Name'];
            $collection = Collection::where("mongo_object_id", $collectionMongoId);

            if ($dryRun) {
                $this->info("Team ID: {$collection->first()->id}, Old Logo: {$collection->image_link}, New Logo: {$imagePath}");
            } else {
                $collection->update(['image_link' => $imagePath]);
            }



            $progressbar->advance();
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
