<?php

namespace App\Observers;

use App\Jobs\IndexDataset;
use App\Models\Dataset;
use App\Models\ProjectGrant;
use App\Models\ProjectGrantVersionHasDataset;

class ProjectGrantObserver
{
    public function created(ProjectGrant $projectGrant): void
    {
        $this->dispatchReindexLinkedDatasets($projectGrant->id);
    }

    public function updated(ProjectGrant $projectGrant): void
    {
        $this->dispatchReindexLinkedDatasets($projectGrant->id);
    }

    public function deleted(ProjectGrant $projectGrant): void
    {
        $this->dispatchReindexLinkedDatasets($projectGrant->id);
    }

    public function restored(ProjectGrant $projectGrant): void
    {
        //
    }

    public function forceDeleted(ProjectGrant $projectGrant): void
    {
        //
    }

    private function dispatchReindexLinkedDatasets(int $projectGrantId): void
    {
        $datasetIds = Dataset::query()
            ->where('status', Dataset::STATUS_ACTIVE)
            ->whereIn(
                'id',
                ProjectGrantVersionHasDataset::query()
                    ->where('project_grant_id', $projectGrantId)
                    ->select('dataset_id')
            )
            ->pluck('id');

        foreach ($datasetIds as $datasetId) {
            IndexDataset::dispatch((string) $datasetId);
        }
    }
}
