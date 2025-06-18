<?php

namespace App\Console\Commands;

use App\Models\Collection;
use Illuminate\Console\Command;

class MakeCollectionsActive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-collections-active';

    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/collections_make_active.csv');

        $progressbar = $this->output->createProgressBar(count($this->csvData));
        foreach ($this->csvData as $csv) {
            $collectionName = trim($csv['Name']);
            if ($collectionName == "") {
                $progressbar->advance();
                continue;
            }

            $match = Collection::where("name", $collectionName);
            $count = $match->count();
            if (!$count) {
                echo "ERROR --> Not found --> '$collectionName'";
            } elseif ($count > 1) {
                $found = $match->select("name")->get();
                echo "WARNING--> Duplicate collections found json_encode($found) \n";
            }

            $publicFlag = (int) $csv['Publicflag'];
            if ($publicFlag === 1) {
                $match->update(['status' => Collection::STATUS_ACTIVE]);
            } else {
                $match->update(['status' => Collection::STATUS_DRAFT]);
            }
            $progressbar->advance();
        }
        $progressbar->finish();
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
