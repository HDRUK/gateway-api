<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\Publication;
use App\Models\Team;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Collection;
use App\Jobs\TermExtraction; 
use Illuminate\Console\Command;
use App\Http\Controllers\Api\V1\PublicationController;
use App\Http\Controllers\Api\V1\ToolController;
use App\Http\Controllers\Api\V1\DurController;
use App\Http\Controllers\Api\V1\CollectionController;
use MetadataManagementController AS MMC;

class ReindexEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reindex-entities {entity?} {sleep=0} {--term-extraction}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to reindex all entities';

    /**
     * The sleep time in microseconds.
     *
     * @var float
     */
    protected $sleepTimeInMicroseconds = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
      
        $sleep = $this->argument("sleep");
        $this->sleepTimeInMicroseconds = floatval($sleep) * 1000 * 1000;
        echo "Sleeping between each reindex by " .  $this->sleepTimeInMicroseconds . "\n";

        $entity = $this->argument('entity');

        if ($entity && method_exists($this, $entity)) {
            $this->$entity();
        } else {
            $this->error('Please provide a valid entity to reindex.');
        }
    }

    private function datasets($termExtraction = false){
        $datasetIds = Dataset::pluck('id');
        $progressbar = $this->output->createProgressBar(count($datasetIds));
        foreach ($datasetIds as $id) {
            
            if ($termExtraction) {
                $dataset = Dataset::where('id', $id)->first();
                if ($dataset->status === Dataset::STATUS_ACTIVE) {
                    $latestMetadata = $dataset->latestMetadata()->first();
                    if ($latestMetadata) {
                        TermExtraction::dispatch(
                            $dataset->id,
                            $dataset->lastMetadataVersionNumber()->version,
                            base64_encode(gzcompress(gzencode(json_encode($latestMetadata->metadata)), 6))
                        );
                    }
                }
            }
            MMC::reindexElastic($id);
            usleep($this->sleepTimeInMicroseconds);
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function tools(){
        $toolController = new ToolController();
        $toolIds = Tool::pluck('id');
        $progressbar = $this->output->createProgressBar(count($toolIds));
        foreach ($toolIds as $id) {
            $toolController->indexElasticTools($id);
            usleep($this->sleepTimeInMicroseconds);
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function publications(){
        $publicationController = new PublicationController();
        $pubicationIds = Publication::pluck('id');
        $progressbar = $this->output->createProgressBar(count($pubicationIds));
        foreach ($pubicationIds as $id) {
            $publicationController->indexElasticPublication($id);
            usleep($this->sleepTimeInMicroseconds); 
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function durs(){
        $durController = new DurController();
        $durIds = Dur::pluck('id');
        $progressbar = $this->output->createProgressBar(count($durIds));
        foreach ($durIds as $id) {
            $durController->indexElasticDur($id);
            usleep($this->sleepTimeInMicroseconds); 
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function collections(){
        $collectionController = new CollectionController();
        $collectionIds = Collection::pluck('id');
        $progressbar = $this->output->createProgressBar(count($collectionIds));
        foreach ($collectionIds as $id) {
            $collectionController->indexElasticCollections($id);
            usleep($this->sleepTimeInMicroseconds); 
            $progressbar->advance();
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
            usleep($this->sleepTimeInMicroseconds);
        }
        $progressbar->finish();
    }

}
