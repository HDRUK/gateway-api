<?php

namespace App\Console\Commands;

use Config;
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

        $this->migrateAccessServiceCategory();
        $this->curateDatasets();

        if ($reindexEnabled) {
            $datasetIds = Dataset::pluck('id');
            $progressbar = $this->output->createProgressBar(count($datasetIds));
            foreach ($datasetIds as $id) {
                MMC::reindexElastic($id);
                sleep(1); // to not kill ElasticSearch
                $progressbar->advance();
            }
            $progressbar->finish();
        }  

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
            }
            $progressbar->advance();
           
        }

        $progressbar->finish();
    }
 
    private function curateDatasets()
    {
        if(version_compare(Config::get('metadata.GWDM.version'),"2.0","<")){
            $this->error("You cannot run this script for GWDM versions older than 2.0");
            return;
        }
        $this->info('');
        $this->info('Curating datasets...');

        $doiPattern = '/^10.\d{4,9}\/[-._;()\/:a-zA-Z0-9]+$/';

        $csvData = $this->readMigrationFile(storage_path() . '/migration_files/datasets_curation_july2024.csv');
               
        $progressbar = $this->output->createProgressBar(count($csvData));
        $progressbar->start();

        foreach ($csvData as $csv) {
            $datasetid = $csv['id'];
        
            $dataset = Dataset::where([
                'datasetid' => $datasetid,
            ])->first();

            if (!$dataset) {
                //$this->warn("Not found datasetid=". $datasetid);
                continue;
            }
            $datasetVersion = DatasetVersion::where([
                'id' => $dataset->id,
            ])->first();

            if (!$datasetVersion) {
                $this->error('cannot find a datasetverison associated with this dataset');
                continue;
            }
            $metadata = $datasetVersion->metadata;

            $doiName = $csv['doiName'];
            $datasetType = $csv['Mapped Data type'];
            $datasetSubType = $csv['Mapped Data sub type'];

            $associatedMedia = $csv['Annotation note link'];
            $toolLink = $csv['Github tool link'];
            
            //note: to be implemented in the future
            //- this was an AC dropped from GAT-4404 
            //$syntheticDataWebLink = $csv['Synthetic dataset link'];
            
            // Check if the variable matches the pattern
            if($doiName) {   
                if (preg_match($doiPattern, $doiName)) {
                    $metadata['metadata']['summary']['doiName'] = $doiName;
                }
                else{
                    $this->warn($doiName . ' is not a valid doi');
                }
            }

            if($datasetType){
                $metadata['metadata']['summary']['datasetType'] = $datasetType;
            }

            if($datasetSubType){
                $metadata['metadata']['summary']['datasetSubType'] = $datasetSubType;
            }

            DatasetVersion::where('id', $dataset->id)->update([
                'metadata' => json_encode(json_encode($metadata)),
            ]);

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
