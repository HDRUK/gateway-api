<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dataset;
use App\Jobs\TermExtraction; 

class ReindexDatasets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datasets:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all active datasets in ElasticSearch';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Fetch all datasets
        $datasets = Dataset::all();

        foreach ($datasets as $dataset) {
            if ($dataset->status === Dataset::STATUS_ACTIVE) {
                // Retrieve the latest metadata
                $latestMetadata = $dataset->latestMetadata()->first();

                if ($latestMetadata) {
                    // Dispatch the TermExtraction job
                    TermExtraction::dispatch(
                        $dataset->id,
                        $dataset->lastMetadataVersionNumber()->version,
                        base64_encode(gzcompress(gzencode(json_encode($latestMetadata->metadata)), 6)),
                        "on"
                    );
                    $this->info('Index: ' . $dataset->id);
                } else {
                    $this->error('No latest metadata found for dataset ID: ' . $dataset->id);
                }
            }
        }

        $this->info('All active datasets reindexed successfully.');

        return 0;
    }
}
