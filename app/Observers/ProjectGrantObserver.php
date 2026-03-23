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
        // Project grants are linked through dataset versions.
        $rows = \DB::table('project_grant_has_dataset_version')
            ->join('dataset_versions', 'dataset_versions.id', '=', 'project_grant_has_dataset_version.dataset_version_id')
            ->join('datasets', 'datasets.id', '=', 'dataset_versions.dataset_id')
            ->where('project_grant_has_dataset_version.project_grant_id', $projectGrantId)
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

