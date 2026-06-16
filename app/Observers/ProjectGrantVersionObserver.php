<?php

namespace App\Observers;

use App\Http\Traits\IndexElastic;
use App\Models\Dataset;
use App\Models\ProjectGrantVersion;

class ProjectGrantVersionObserver
{
    use IndexElastic;

    public function created(ProjectGrantVersion $projectGrantVersion): void
    {
        $this->reindexForVersion($projectGrantVersion->id);
    }

    public function updated(ProjectGrantVersion $projectGrantVersion): void
    {
        $this->reindexForVersion($projectGrantVersion->id);
    }

    public function deleted(ProjectGrantVersion $projectGrantVersion): void
    {
        $this->reindexForVersion($projectGrantVersion->id);
    }

    public function restored(ProjectGrantVersion $projectGrantVersion): void
    {
        //
    }

    public function forceDeleted(ProjectGrantVersion $projectGrantVersion): void
    {
        //
    }

    private function reindexForVersion(int $projectGrantVersionId): void
    {
        $version = ProjectGrantVersion::withTrashed()
            ->where('id', $projectGrantVersionId)
            ->select(['id', 'project_grant_id'])
            ->first();

        if (!$version) {
            return;
        }

        $rows = \DB::table('project_grant_has_dataset')
            ->join('datasets', 'datasets.id', '=', 'project_grant_has_dataset.dataset_id')
            ->where('project_grant_has_dataset.project_grant_id', $version->project_grant_id)
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
