<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\Publication;
use App\Models\Team;
use Illuminate\Console\Command;
use App\Http\Controllers\Api\V1\PublicationController;
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

    private function datasets(){
        $datasetIds = Dataset::pluck('id');
        $progressbar = $this->output->createProgressBar(count($datasetIds));
        foreach ($datasetIds as $id) {
            $dataset = Dataset::find($id);
            if($dataset){
                MMC::reindexElastic($dataset->id);
            }
            $progressbar->advance();
            sleep(0.5); 
        }
        $progressbar->finish();
    }

    private function publications(){
        $publicationController = new PublicationController();
        $pubicationIds = Publication::pluck('id');
        $progressbar = $this->output->createProgressBar(count($pubicationIds));
        foreach ($pubicationIds as $id) {
          
            $publicationController->indexElasticPublication($id);
           
            $progressbar->advance();
            sleep(0.5); 
        }
        $progressbar->finish();
    }

    private function dataProviders(){
        $providerIds = array_unique(Dataset::pluck('team_id')->toArray());
        $progressbar = $this->output->createProgressBar(count($providerIds));
        foreach ($providerIds as $id) {
            $team = Team::find($id);
            if($team){
                MMC::reindexElasticDataProvider($team->id);
            }
            $progressbar->advance();
            sleep(0.5); 
        }
        $progressbar->finish();
    }


}
