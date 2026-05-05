<?php

namespace App\Observers;

use App\Http\Traits\IndexElastic;
use App\Models\Dataset;
use App\Models\ProjectGrantVersionHasDataset;

class ProjectGrantHasDatasetVersionObserver
{
    use IndexElastic;

    public function created(ProjectGrantVersionHasDataset $pivot): void
    {
        $this->reindexForPivot($pivot);
    }

    public function updated(ProjectGrantVersionHasDataset $pivot): void
    {
        $this->reindexForPivot($pivot);
    }

    public function deleted(ProjectGrantVersionHasDataset $pivot): void
    {
        $this->reindexForPivot($pivot);
    }

    public function restored(ProjectGrantVersionHasDataset $pivot): void
    {
        //
    }

    public function forceDeleted(ProjectGrantVersionHasDataset $pivot): void
    {
        //
    }

    private function reindexForPivot(ProjectGrantVersionHasDataset $pivot): void
    {
        $dataset = Dataset::where([
            'id' => $pivot->dataset_id,
            'status' => Dataset::STATUS_ACTIVE,
        ])->select(['id', 'team_id'])->first();

        if (!$dataset) {
            return;
        }

        $this->reindexElastic((string) $dataset->id);
        if ($dataset->team_id) {
            $this->reindexElasticDataProviderWithRelations((int) $dataset->team_id, 'dataset');
        }
    }
}
