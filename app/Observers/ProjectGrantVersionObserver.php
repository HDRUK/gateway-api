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
        $rows = \DB::table('project_grant_has_dataset_version')
            ->join('dataset_versions', 'dataset_versions.id', '=', 'project_grant_has_dataset_version.dataset_version_id')
            ->join('datasets', 'datasets.id', '=', 'dataset_versions.dataset_id')
            ->where('project_grant_has_dataset_version.project_grant_version_id', $projectGrantVersionId)
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
