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

class ReindexEntities extends Command
{
    use IndexElastic;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'app:reindex-entities {entity?} {sleep=0} {minIndex?} {maxIndex?} {--term-extraction}';

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
     * Execute the console command.
     */
    public function handle()
    {

        $sleep = $this->argument("sleep");
        $this->sleepTimeInMicroseconds = floatval($sleep) * 1000 * 1000;
        echo 'Sleeping between each reindex by ' .  $this->sleepTimeInMicroseconds . "\n";

        $entity = $this->argument('entity');

        $this->minIndex = is_null($this->argument('minIndex')) ? null : (int) $this->argument('minIndex');
        $this->maxIndex = is_null($this->argument('maxIndex')) ? null : (int) $this->argument('maxIndex');

        if ($entity && method_exists($this, $entity)) {
            $this->$entity();
        } else {
            $this->error('Please provide a valid entity to reindex.');
        }
    }


    private function datasets()
    {
        $minIndex = $this->minIndex;
        $maxIndex = $this->maxIndex;
        $datasetIds = Dataset::where("status", Dataset::STATUS_ACTIVE)
            ->pluck('id')->toArray();

        if (isset($minIndex) && isset($maxIndex)) {
            $datasetIds = array_slice($datasetIds, $minIndex, $maxIndex - $minIndex + 1);
        } elseif (isset($minIndex)) {
            $datasetIds = array_slice($datasetIds, $minIndex);
        } elseif (isset($maxIndex)) {
            $datasetIds = array_slice($datasetIds, 0, $maxIndex + 1);
        }

        $termExtraction = $this->option('term-extraction'); // Initialize $termExtraction based on the command option

        $progressbar = $this->output->createProgressBar(count($datasetIds));
        foreach ($datasetIds as $id) {
            if ($termExtraction) {
                $dataset = Dataset::where('id', $id)->first();
                $latestMetadata = $dataset->latestMetadata()->first();
                TermExtraction::dispatch(
                    $id,
                    $dataset->lastMetadataVersionNumber()->version,
                    base64_encode(gzcompress(gzencode(json_encode($latestMetadata->metadata)), 6))
                );
            } else {
                $this->reindexElastic($id);
            }
            usleep($this->sleepTimeInMicroseconds);
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function tools()
    {
        $toolIds = Tool::pluck('id');
        $progressbar = $this->output->createProgressBar(count($toolIds));
        foreach ($toolIds as $id) {
            $this->indexElasticTools($id);
            usleep($this->sleepTimeInMicroseconds);
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function publications()
    {
        $pubicationIds = Publication::pluck('id');
        $progressbar = $this->output->createProgressBar(count($pubicationIds));
        foreach ($pubicationIds as $id) {
            $this->indexElasticPublication($id);
            usleep($this->sleepTimeInMicroseconds);
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function durs()
    {
        $durIds = Dur::pluck('id');
        $progressbar = $this->output->createProgressBar(count($durIds));
        foreach ($durIds as $id) {
            $this->indexElasticDur($id);
            usleep($this->sleepTimeInMicroseconds);
            $progressbar->advance();
        }
        $progressbar->finish();
    }

    private function collections()
    {
        $collectionIds = Collection::pluck('id');
        $progressbar = $this->output->createProgressBar(count($collectionIds));
        foreach ($collectionIds as $id) {
            $this->indexElasticCollections($id);
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
                $this->reindexElasticDataProvider($team->id);
            }
            $progressbar->advance();
            usleep($this->sleepTimeInMicroseconds);
        }
        $progressbar->finish();
    }
}
