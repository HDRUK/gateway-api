<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Traits\IndexElastic;
use App\Models\Dataset;

class IndexDocument implements ShouldQueue
{
    use Queueable;
    use IndexElastic;

    private string $documentId;

    /**
     * Create a new job instance.
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dataset = $this->documentId;
        $this->reindexElastic($dataset);

        $datasetInfo = Dataset::where('id', (int)$dataset)->select('team_id')->first();

        if ($datasetInfo->team_id) {
            $this->reindexElasticDataProviderWithRelations((int) $datasetInfo->team_id, 'dataset');
        }
    }
}
