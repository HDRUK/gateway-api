<?php

namespace App\Observers;

use App\Http\Traits\IndexElastic;
use App\Models\Dataset;
use App\Models\ProjectGrantHasDatasetVersion;

class ProjectGrantHasDatasetVersionObserver
{
    use IndexElastic;

    public function created(ProjectGrantHasDatasetVersion $pivot): void
    {
        $this->reindexForPivot($pivot);
    }

    public function updated(ProjectGrantHasDatasetVersion $pivot): void
    {
        $this->reindexForPivot($pivot);
    }

    public function deleted(ProjectGrantHasDatasetVersion $pivot): void
    {
        $this->reindexForPivot($pivot);
    }

    public function restored(ProjectGrantHasDatasetVersion $pivot): void
    {
        //
    }

    public function forceDeleted(ProjectGrantHasDatasetVersion $pivot): void
    {
        //
    }

    private function reindexForPivot(ProjectGrantHasDatasetVersion $pivot): void
    {
        $datasetVersion = \DB::table('dataset_versions')
            ->where('id', $pivot->dataset_version_id)
            ->select('dataset_id')
            ->first();

        if (!$datasetVersion) {
            return;
        }

        $dataset = Dataset::where([
            'id' => $datasetVersion->dataset_id,
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

