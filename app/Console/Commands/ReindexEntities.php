<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\Team;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Collection;
use Illuminate\Console\Command;
use App\Http\Controllers\Api\V1\PublicationController;
use App\Http\Controllers\Api\V1\ToolController;
use App\Http\Controllers\Api\V1\DurController;
use App\Http\Controllers\Api\V1\CollectionController;
use MetadataManagementController as MMC;

class ReindexEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reindex-entities {entity?} {sleep=0} {minIndex?} {maxIndex?}';

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
     * Specific index to start run from
     *
     * @var int
     */
    protected $minIndex = 0;

    /**
     * Specific index to end run
     *
     * @var int
     */
    protected $maxIndex = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $sleep = $this->argument("sleep");
        $this->sleepTimeInMicroseconds = floatval($sleep) * 1000 * 1000;
        echo "Sleeping between each reindex by " .  $this->sleepTimeInMicroseconds . "\n";

        $entity = $this->argument('entity');

        $this->minIndex = $this->argument('minIndex');
        $this->maxIndex = $this->argument('maxIndex');

        if ($entity && method_exists($this, $entity)) {
            $this->$entity();
        } else {
            $this->error('Please provide a valid entity to reindex.');
        }
    }

    private function checkAndCleanMaterialType($id)
    {

        $datasetVersion = DatasetVersion::where([
            'id' => $id,
        ])->first();

        $metadata = $datasetVersion->metadata;

        if (array_key_exists('tissuesSampleCollection', $metadata['metadata'])) {
            if (!is_null($metadata['metadata']['tissuesSampleCollection'])) {
                $tissues = $metadata['metadata']['tissuesSampleCollection'];

                // Check if $tissues is set to [[]] and set it to [] if so
                if ($tissues === [[]]) {
                    $metadata['metadata']['tissuesSampleCollection'] = [];
                    \Log::info("Found bad data and cleaned it!");

                    DatasetVersion::where('id', $datasetVersion->id)->update([
                        'metadata' => json_encode(json_encode($metadata)),
                    ]);
                }
            }
        }

        //DatasetVersion::where('id', $datasetVersion->id)->update([
        //    'metadata' => json_encode(json_encode($metadata)),
        //]);
    }

    private function datasets()
    {
        $minIndex = $this->minIndex;
        $maxIndex = $this->maxIndex;
        $datasetIds = Dataset::pluck('id')->toArray();
        if (isset($minIndex) && isset($maxIndex)) {
            $datasetIds = array_slice($datasetIds, $minIndex, $maxIndex - $minIndex + 1);
        } elseif (isset($minIndex)) {
            $datasetIds = array_slice($datasetIds, $minIndex);
        } elseif (isset($maxIndex)) {
            $datasetIds = array_slice($datasetIds, 0, $maxIndex + 1);
        }

        $progressbar = $this->output->createProgressBar(count($datasetIds));
        foreach ($datasetIds as $id) {
            $this->checkAndCleanMaterialType($id);
            //MMC::reindexElastic($id);
            usleep($this->sleepTimeInMicroseconds);
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function tools()
    {
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

    private function publications()
    {
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

    private function durs()
    {
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

    private function collections()
    {
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

    private function dataProviders()
    {
        $providerIds = array_unique(Dataset::pluck('team_id')->toArray());
        $progressbar = $this->output->createProgressBar(count($providerIds));
        foreach ($providerIds as $id) {
            $team = Team::find($id);
            if ($team) {
                MMC::reindexElasticDataProvider($team->id);
            }
            $progressbar->advance();
            usleep($this->sleepTimeInMicroseconds);
        }
        $progressbar->finish();
    }
}
