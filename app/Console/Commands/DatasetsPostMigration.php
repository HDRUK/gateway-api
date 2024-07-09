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

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to post-process migrated datasets from mk1 mongo db. Update accessServiceCategory with values from file by pid.';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reindex = $this->argument('reindex');
        $reindexEnabled = $reindex !== null;

    
        //$this->migrateAccessServiceCategory();
        $this->curateDatasets();

        /*
        if ($reindexEnabled) {
            MMC::reindexElastic($dataset->id);
        }   
        */

    }

    /**
     * Execute the console command.
     */
    private function migrateAccessServiceCategory()
    {

        $csvData = $this->readMigrationFile(storage_path() . '/migration_files/mapped_mk1_access_service_category.csv');

        
        $progressbar = $this->output->createProgressBar(count($csvData));
        $progressbar->start();

        foreach ($csvData as $csv) {
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
                    sleep(1); // to not kill ElasticSearch
                }                
            }
            $progressbar->advance();
           
        }

        $progressbar->finish();
    }
 
    private function curateDatasets()
    {

        $csvData = $this->readMigrationFile(storage_path() . '/migration_files/datasets_curation_july2024.csv');
               
        $progressbar = $this->output->createProgressBar(count($csvData));
        $progressbar->start();

        foreach ($csvData as $csv) {
            $datasetid = $csv['id'];
        
            $dataset = Dataset::where([
                'datasetid' => $datasetid,
            ])->first();

            if (!$dataset) {
                $this->warn("Not found datasetid=". $datasetid);
                continue;
            }
            $datasetVersion = DatasetVersion::where([
                'id' => $dataset->id,
            ])->first();

            if (!$datasetVersion) {
                $this->error("cannot find a datasetverison associated with this dataset");
                continue;
            }
            $metadata = $datasetVersion->metadata['metadata'];

            $doiName = $csv['doiName'];
            $datasetType = $csv['Mapped Data type'];
            $datasetSubType = $csv['Mapped Data sub type'];

            $associatedMedia = $csv['Annotation note link'];
            $toolLink = $csv['Github tool link'];
            $syntheticDataWebLink = $csv['Synthetic dataset link'];

            if($doiName){
                $this->info($metadata['summary']['doiName']);
                $this->warn($doiName);
                $this->info("...Updating doiName");
            }

            $progressbar->advance();

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
