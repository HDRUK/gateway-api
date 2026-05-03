<?php

namespace App\Observers;

use App\Http\Traits\IndexElastic;
use App\Models\Dataset;
use App\Models\ProjectGrant;

class ProjectGrantObserver
{
    use IndexElastic;

    public function created(ProjectGrant $projectGrant): void
    {
        $this->reindexLinkedDatasets($projectGrant->id);
    }

    public function updated(ProjectGrant $projectGrant): void
    {
        $this->reindexLinkedDatasets($projectGrant->id);
    }

    public function deleted(ProjectGrant $projectGrant): void
    {
        $this->reindexLinkedDatasets($projectGrant->id);
    }

    public function restored(ProjectGrant $projectGrant): void
    {
        //
    }

    public function forceDeleted(ProjectGrant $projectGrant): void
    {
        //
    }

    private function reindexLinkedDatasets(int $projectGrantId): void
    {
        $rows = \DB::table('project_grant_has_dataset')
            ->join('datasets', 'datasets.id', '=', 'project_grant_has_dataset.dataset_id')
            ->where('project_grant_has_dataset.project_grant_id', $projectGrantId)
            ->where('datasets.status', Dataset::STATUS_ACTIVE)
            ->select('datasets.id', 'datasets.team_id')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            $this->reindexElastic((string) $row->id);
            if ($row->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $row->team_id, 'dataset');
            }
        }
    }
}
