<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use MetadataManagementController AS MMC;

class DatasetsPostMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:datasets-post-migration {reindex?}';

    private $csvData = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to post-process migrated datasets from mk1 mongo db. Update accessServiceCategory with values from file by pid.';

    public function __construct()
    {
        parent::__construct();
        $this->csvData = $this->readMigrationFile(storage_path() . '/migration_files/mapped_mk1_access_service_category.csv');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reindex = $this->argument('reindex');
        $reindexEnabled = $reindex !== null;

        $progressbar = $this->output->createProgressBar(count($this->csvData));
        $progressbar->start();

        foreach ($this->csvData as $csv) {
            $mongoPid = $csv['pid'];
            $mapped = $csv['Mapped'];
            $dataset = Dataset::where([
                'mongo_pid' => $mongoPid,
            ])->first();

            if ($dataset) {
                $datasetVersion = DatasetVersion::where([
                    'id' => $dataset->id,
                ])->first();

                if ($datasetVersion) {
                    $metadata = $datasetVersion->metadata;

                    if(array_key_exists('accessServiceCategory', $metadata['metadata']['accessibility']['access'])) {
                        $metadata['metadata']['accessibility']['access']['accessServiceCategory'] = $mapped;
                    }

                    DatasetVersion::where('id', $dataset->id)->update([
                        'metadata' => json_encode(json_encode($metadata)),
                    ]);
                }

                if ($reindexEnabled) {
                    MMC::reindexElastic($dataset->id);
                }                
            }
            $progressbar->advance();
            sleep(1); // to not kill ElasticSearch
        }

        $progressbar->finish();
    }

    private function readMigrationFile(string $migrationFile): array
    {
        $response = [];
        $file = fopen($migrationFile, 'r');
        $headers = fgetcsv($file);

        while (($row = fgetcsv($file)) !== FALSE) {
            $item = [];
            foreach ($row as $key => $value) {
                $item[$headers[$key]] = $value ?: '';
            }

            $response[] = $item;
        }

        fclose($file);
        
        return $response;
    }
}
