<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Traits\IndexElastic;

class DeindexDataset implements ShouldQueue
{
    use Queueable;
    use IndexElastic;

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
