<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Jobs\TermExtraction;
use Illuminate\Console\Command;

class ReindexEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'app:reindex-entities
                            {entity?}
                            {--minIndex=}
                            {--maxIndex=}
                            {--term-extraction}
                            {--all-terms}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI command to re-run term extraction for datasets';

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
     * Execute the console command.
     */
    public function handle()
    {

        $entity = $this->argument('entity');
        $this->minIndex = $this->option('minIndex') !== null ? (int) $this->option('minIndex') : null;
        $this->maxIndex = $this->option('maxIndex') !== null ? (int) $this->option('maxIndex') : null;

        $this->termExtraction = $this->option('term-extraction');
        $this->allTerms = (bool) $this->option('all-terms');

        if ($entity && method_exists($this, $entity)) {
            $this->$entity();
        } else {
            $this->error('Please provide a valid entity to reindex.');
        }
    }


    private function datasets()
    {
        if (!$this->termExtraction) {
            $this->error('Only --term-extraction is supported for datasets.');
            return;
        }

        $datasetIds = Dataset::where("status", Dataset::STATUS_ACTIVE)
            ->select("id")
            ->pluck('id')
            ->toArray();

        $this->sliceIds($datasetIds);
        $this->rerunTermExtraction($datasetIds);
    }

    private function rerunTermExtraction(array $ids)
    {
        $progressbar = $this->output->createProgressBar(count($ids));

        foreach ($ids as $id) {
            $dataset = Dataset::where('id', $id)->first();
            $latestMetadata = $dataset->latestMetadata()->first();
            $datasetVersionId = $latestMetadata->id;
            $versionNumber = $dataset->lastMetadataVersionNumber()->version;

            $tedData = $this->allTerms ? $latestMetadata->metadata['metadata'] : $latestMetadata->metadata['metadata']['summary'];

            TermExtraction::dispatch(
                $id,
                $datasetVersionId,
                $versionNumber,
                base64_encode(gzcompress(gzencode(json_encode($tedData)))),
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
