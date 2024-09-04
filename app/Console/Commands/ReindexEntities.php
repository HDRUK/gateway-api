<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Models\Publication;
use App\Models\Team;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Collection;
use App\Jobs\TermExtraction;
use App\Http\Traits\IndexElastic;
use Illuminate\Console\Command;

use ElasticClientController as ECC;

class ReindexEntities extends Command
{
    use IndexElastic;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'app:reindex-entities 
                            {entity?} 
                            {--sleep=0} 
                            {--minIndex=} 
                            {--maxIndex=} 
                            {--chunkSize=10} 
                            {--term-extraction} 
                            {--fresh}';
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
     * @var int|null
     */
    protected $minIndex = null;

    /**
     * Specific index to end run
     *
     * @var int|null
     */
    protected $maxIndex = null;

    /**
     * Specific the chunk size
     *
     * @var int|null
     */
    protected $chunkSize = null;

    /**
     * Should term extraction be re run
     *
     * @var boolean
     */
    protected $termExtraction = false;

    /**
     * Run fresh
     *
     * @var boolean
     */
    protected $fresh = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $entity = $this->argument('entity');
        $sleep = $this->option("sleep");
        $this->sleepTimeInMicroseconds = floatval($sleep) * 1000 * 1000;
        echo 'Sleeping between each reindex by ' .  $this->sleepTimeInMicroseconds . "\n";

        $this->minIndex = (int) $this->option('minIndex');
        $this->maxIndex = (int) $this->option('maxIndex');
        $this->chunkSize = (int) $this->option('chunkSize');
        $this->termExtraction = $this->option('term-extraction');
        $this->fresh = $this->option('fresh');

        if ($entity && method_exists($this, $entity)) {
            $this->$entity();
        } else {
            $this->error('Please provide a valid entity to reindex.');
        }
    }


    private function datasets()
    {
        $beforeCount = ECC::countDocuments(ECC::ELASTIC_NAME_DATASET);
        echo "Before reindexing there were $beforeCount datasets indexed \n";

        if ($this->fresh){
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_DATASET);
            echo "Deleted $nDeleted documents from the index \n";
        }

        $nTotal = Dataset::count();

        $datasetIds = Dataset::where("status", Dataset::STATUS_ACTIVE)
            ->select("id")
            ->pluck('id')
            ->toArray();

        if ($this->termExtraction) {
            $this->termReExtraction($datasetIds);
        } else {
            $this->sliceIds($datasetIds);
            $this->bulkProcess($datasetIds, 'reindexElastic');
        }

        //sleep for 5 seconds and count again..
        usleep(5 * 1000 * 1000);
        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_DATASET);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";
        echo "\nAfter reindexing there were $nIndexed datasets indexed \n";

    }

    private function tools()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_TOOL);
            echo "---> Deleted $nDeleted documents from the index \n";
        }

        $nTotal = Tool::count();
        $toolIds = Tool::where("status", Tool::STATUS_ACTIVE)
            ->select("id")
            ->pluck('id')
            ->toArray();
        $this->sliceIds($toolIds);
        $this->bulkProcess($toolIds, 'indexElasticTools');

        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_DUR);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";
    }

    private function publications()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_PUBLICATION);
            echo "---> Deleted $nDeleted documents from the index \n";
        }
        $nTotal = Publication::count();
        $publicationIds = Publication::where("status", Publication::STATUS_ACTIVE)
            ->select("id")
            ->pluck('id')
            ->toArray();
        $this->sliceIds($publicationIds);

        $this->bulkProcess($publicationIds, 'indexElasticPublication');

        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_PUBLICATION);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";
    }

    private function durs()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_DUR);
            echo "---> Deleted $nDeleted documents from the index \n";
        }

        $nTotal = Dur::count();
        $durIds = Dur::where("status", Publication::STATUS_ACTIVE)
            ->select("id")
            ->pluck('id')
            ->toArray();
        $this->sliceIds($durIds);

        $this->bulkProcess($durIds, 'indexElasticDur');

        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_DUR);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";
    }

    private function collections()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_COLLECTION);
            echo "---> Deleted $nDeleted documents from the index \n";
        }

        $nTotal = Collection::count();
        $collectionIds = Collection::where("status", Collection::STATUS_ACTIVE)
            ->select("id")
            ->pluck('id')
            ->toArray();
        $this->sliceIds($collectionIds);

        $this->bulkProcess($collectionIds, 'indexElasticCollections');

        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_COLLECTION);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";

    }

    private function dataProviders()
    {
        $providerIds = array_unique(Dataset::pluck('team_id')->toArray());
        $teamIds = Team::whereIn('id', $providerIds)->select('id')
            ->pluck('id')->toArray();

        $this->bulkProcess($teamIds, 'reindexElasticDataProvider');
    }

    private function bulkProcess(array $ids, string $indexerName)
    {
        $progressbar = $this->output->createProgressBar(count($ids));
        $chunks = array_chunk($ids, $this->chunkSize);
        foreach ($chunks as $ids) {
            $this->reindexElasticBulk($ids, [$this, $indexerName]);
            $progressbar->advance(count($ids));
            usleep($this->sleepTimeInMicroseconds);
        }
        $progressbar->finish();
    }

    private function termReExtraction(array $ids)
    {
        $progressbar = $this->output->createProgressBar(count($ids));

        echo "Running term extraction \n";

        foreach ($ids as $id) {
            $dataset = Dataset::where('id', $id)->first();
            $latestMetadata = $dataset->latestMetadata()->first();
            $versionNumber = $dataset->lastMetadataVersionNumber()->version;
            $elasticIndexing = true;

            TermExtraction::dispatch(
                $id,
                $versionNumber,
                base64_encode(gzcompress(gzencode(json_encode($latestMetadata->metadata)), 6)),
                $elasticIndexing
            );
        }
        $progressbar->finish();
    }

    public function sliceIds(array &$ids): void
    {
        $minIndex = $this->minIndex;
        $maxIndex = $this->maxIndex;

        if (isset($minIndex) && isset($maxIndex)) {
            $ids = array_slice($ids, $minIndex, $maxIndex - $minIndex + 1);
        } elseif (isset($minIndex)) {
            $ids = array_slice($ids, $minIndex);
        } elseif (isset($maxIndex)) {
            $ids = array_slice($ids, 0, $maxIndex + 1);
        }
    }
}
