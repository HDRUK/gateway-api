<?php

namespace App\Observers;

use App\Jobs\IndexDataset;
use App\Models\Dataset;
use App\Models\ProjectGrantVersionHasDataset;

class ProjectGrantHasDatasetVersionObserver
{
    public function created(ProjectGrantVersionHasDataset $pivot): void
    {
        $this->dispatchReindexForPivot($pivot);
    }

    public function updated(ProjectGrantVersionHasDataset $pivot): void
    {
        $this->dispatchReindexForPivot($pivot);
    }

    public function deleted(ProjectGrantVersionHasDataset $pivot): void
    {
        $this->dispatchReindexForPivot($pivot);
    }

    public function restored(ProjectGrantVersionHasDataset $pivot): void
    {
        //
    }

    public function forceDeleted(ProjectGrantVersionHasDataset $pivot): void
    {
        //
    }

    private function dispatchReindexForPivot(ProjectGrantVersionHasDataset $pivot): void
    {
        $dataset = Dataset::where([
            'id' => $pivot->dataset_id,
            'status' => Dataset::STATUS_ACTIVE,
        ])->select(['id'])->first();

        if (!$dataset) {
            return;
        }

        IndexDataset::dispatch((string) $dataset->id);
    }
}
