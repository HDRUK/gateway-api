<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;

class DatasetLinkagesMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dataset-linkages';

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
    protected $description = 'CLI command to add linakges for dataset (versions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->readMigrationFile(storage_path() . '/migration_files/dataset_linkages.csv');

        DatasetVersionHasDatasetVersion::truncate();
        foreach ($this->csvData as $csv) {
            $sourceDataset = Dataset::where('mongo_object_id', $csv['source_mongo_object_id'])
                ->select('id')->first();
            if(!$sourceDataset) {
                continue;
            }
            $targetDataset = Dataset::where('mongo_object_id', $csv['target_mongo_object_id'])
                ->select('id')->first();
            if(!$targetDataset) {
                continue;
            }

            $sourceDatasetVersion = DatasetVersion::where('dataset_id', $sourceDataset->id)
                ->select('id')->first();

            $targetDatasetVersion = DatasetVersion::where('dataset_id', $targetDataset->id)
                ->select('id')->first();



            DatasetVersionHasDatasetVersion::create(
                [
                    'dataset_version_source_id' => $sourceDatasetVersion->id,
                    'dataset_version_target_id' => $targetDatasetVersion->id,
                    'linkage_type' => $csv['linkage_type'],
                    'direct_linkage' => $csv['direct_linkage'],
                    'description' => $csv['description']
                 ]
            );
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

}
