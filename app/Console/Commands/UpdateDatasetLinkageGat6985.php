<?php

namespace App\Console\Commands;

use App\Models\Dataset;
use App\Jobs\LinkageExtraction;
use Illuminate\Console\Command;

class UpdateDatasetLinkageGat6985 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-dataset-linkage-gat6985';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $datasets = Dataset::select(['id', 'status'])->get();

        foreach ($datasets as $dataset) {
            $this->info("dataset with ID: {$dataset->id} has status: {$dataset->status}");
            $latestVersion = $dataset->latestVersion();
            $datasetVersionId = $latestVersion->id;
            LinkageExtraction::dispatch($dataset->id, $datasetVersionId);
            $this->info("LinkageExtraction job dispatched for dataset ID: {$dataset->id} and version ID: {$datasetVersionId}");

            $this->info('Done');
        }
    }
}
