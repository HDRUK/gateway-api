<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Console\Command;
use MetadataManagementController AS MMC;

class ReindexEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reindex-entities {entity?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to reindex all entities';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $entity = $this->argument('entity');

        if ($entity && method_exists($this, $entity)) {
            $this->$entity();
        } else {
            $this->error('Please provide a valid entity to reindex.');
        }

    }

    public function datasets(){
       
        $datasetIds = Dataset::pluck('id');
        $progressbar = $this->output->createProgressBar(count($datasetIds));
        foreach ($datasetIds as $id) {
            $dataset = Dataset::find($id);
            //MMC::reindexElastic($dataset->id);
            $progressbar->advance();
            sleep(1); // to not kill ElasticSearch
        }
        
        $progressbar->finish();

    }

}
