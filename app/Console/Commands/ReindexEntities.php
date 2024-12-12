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
use App\Models\DataProviderColl;
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
                            {--all-terms} 
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
     * Specific index to end run
     *
     * @var int|null
     */
    protected $chunkSize = null;

    /**
     * run TED
     *
     * @var boolean
     */
    protected $termExtraction = false;

    /**
     * tell TED to run on all terms (override config.ted.use_partial)
     *
     * @var boolean
     */
    protected $allTerms = false;


    /**
     * Specific index to end run
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

        $this->minIndex = $this->option('minIndex') !== null ? (int) $this->option('minIndex') : null;
        $this->maxIndex = $this->option('maxIndex') !== null ? (int) $this->option('maxIndex') : null;

        $this->chunkSize = (int) $this->option('chunkSize');
        $this->termExtraction = $this->option('term-extraction');
        $this->allTerms = (bool) $this->option('all-terms');

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

        if($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_DATASET);
            echo "Deleted $nDeleted documents from the index \n";
        }

        $datasetIds = Dataset::where("status", Dataset::STATUS_ACTIVE)
            ->select("id")
            ->pluck('id')
            ->toArray();

        $this->sliceIds($datasetIds);

        if ($this->termExtraction) {
            $this->rerunTermExtraction($datasetIds);
        } else {
            $this->bulkProcess($datasetIds, 'reindexElastic');
        }

        //sleep for 5 seconds and count again..
        usleep(5 * 1000 * 1000);
        $afterCount = ECC::countDocuments(ECC::ELASTIC_NAME_DATASET);
        echo "\nAfter reindexing there were $afterCount datasets indexed \n";

    }

    private function tools()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_TOOL);
            echo "---> Deleted $nDeleted documents from the index \n";
        }

        $nTotal = Tool::count();
        $toolIds = Tool::where('status', Tool::STATUS_ACTIVE)
            ->select('id')
            ->pluck('id')
            ->toArray();
        $this->sliceIds($toolIds);
        $this->bulkProcess($toolIds, 'indexElasticTools');

        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_TOOL);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";
    }

    private function publications()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_PUBLICATION);
            echo "---> Deleted $nDeleted documents from the index \n";
        }
        $nTotal = Publication::count();
        $publicationIds = Publication::where('status', Publication::STATUS_ACTIVE)
            ->select('id')
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
        $durIds = Dur::where('status', Publication::STATUS_ACTIVE)
            ->select('id')
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
        $collectionIds = Collection::where('status', Collection::STATUS_ACTIVE)
            ->select('id')
            ->pluck('id')
            ->toArray();
        $this->sliceIds($collectionIds);

        $this->bulkProcess($collectionIds, 'indexElasticCollections');

        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_COLLECTION);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";

    }

    private function dataCustodianNetworks()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_DATACUSTODIANNETWORK);
            echo "---> Deleted $nDeleted documents from the index \n";
        }

        $nTotal = DataProviderColl::count();

        $dataCustodianNetworkIds = DataProviderColl::select('id')
            ->pluck('id')
            ->toArray();
        $this->sliceIds($dataCustodianNetworkIds);

        $this->bulkProcess($dataCustodianNetworkIds, 'indexElasticDataCustodianNetwork');

        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_DATACUSTODIANNETWORK);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";
    }

    private function dataProviders()
    {
        if ($this->fresh) {
            $nDeleted = ECC::deleteAllDocuments(ECC::ELASTIC_NAME_DATAPROVIDER);
            echo "---> Deleted $nDeleted documents from the index \n";
        }
        $providerIds = array_unique(Dataset::pluck('team_id')->toArray());
        $nTotal = count($providerIds);
        $teamIds = Team::whereIn('id', $providerIds)->select('id')
            ->pluck('id')->toArray();

        $this->bulkProcess($teamIds, 'reindexElasticDataProvider');
        $nIndexed = ECC::countDocuments(ECC::ELASTIC_NAME_DATAPROVIDER);
        echo "--->  ($nIndexed/$nTotal) Documents indexed ! \n";
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

    private function rerunTermExtraction(array $ids)
    {
        $progressbar = $this->output->createProgressBar(count($ids));

        foreach ($ids as $id) {
            $dataset = Dataset::where('id', $id)->first();
            $latestMetadata = $dataset->latestMetadata()->first();
            $datasetVersionId = $latestMetadata->id;
            $versionNumber = $dataset->lastMetadataVersionNumber()->version;
            $elasticIndexing = true;

            $tedData = $this->allTerms ? $latestMetadata->metadata['metadata'] : $latestMetadata->metadata['metadata']['summary'];

            TermExtraction::dispatch(
                $id,
                $datasetVersionId,
                $versionNumber,
                base64_encode(gzcompress(gzencode(json_encode($tedData)))),
                $elasticIndexing,
                $this->allTerms === false,
            );

            $progressbar->advance(1);
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
