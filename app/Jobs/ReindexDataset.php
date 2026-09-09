<?php

namespace App\Jobs;

use App\Http\Traits\IndexElastic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReindexDataset implements ShouldQueue
{
    use Queueable;
    use IndexElastic;

    // Only deletes one document and dispatches a batch for the team's
    // relations (see IndexElastic::reindexElasticDataProviderWithRelations) —
    // no longer loops over the team in-process, so this stays fixed-cost
    // regardless of team size.
    public $timeout = 60;

    public function __construct(
        private readonly string $datasetId,
        private readonly ?int $teamId
    ) {
    }

    public function handle(): void
    {
        $this->deleteDatasetFromElastic($this->datasetId);

        if ($this->teamId) {
            $this->reindexElasticDataProviderWithRelations($this->teamId, 'dataset');
        }
    }
}
