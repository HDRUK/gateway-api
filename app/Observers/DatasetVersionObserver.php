<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Traits\IndexElastic;

class DatasetVersionObserver
{
    use IndexElastic;

    /**
     * Handle the DatasetVersion "created" event.
     */
    public function created(DatasetVersion $datasetVersion): void
    {
        $this->elasticDatasetVersion($datasetVersion);
    }

    /**
     * Handle the DatasetVersion "updated" event.
     */
    public function updated(DatasetVersion $datasetVersion): void
    {
        $this->elasticDatasetVersion($datasetVersion);
    }

    /**
     * Handle the DatasetVersion "deleted" event.
     */
    public function deleted(DatasetVersion $datasetVersion): void
    {
        $this->elasticDatasetVersion($datasetVersion);
    }

    /**
     * Handle the DatasetVersion "restored" event.
     */
    public function restored(DatasetVersion $datasetVersion): void
    {
        //
    }

    /**
     * Handle the DatasetVersion "force deleted" event.
     */
    public function forceDeleted(DatasetVersion $datasetVersion): void
    {
        //
    }

    public function elasticDatasetVersion(DatasetVersion $datasetVersion)
    {
        $datasetId = $datasetVersion->dataset_id;
        $dataset = Dataset::where([
            'id' => $datasetId,
            'status' => Dataset::STATUS_ACTIVE,
        ])->select('id', 'status')->first();

        if (!is_null($dataset) && $dataset->status === Dataset::STATUS_ACTIVE) {
            $this->reindexElastic($dataset->id);
            if ($dataset->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dataset->team_id, 'dataset');
            }

        }
    }
}
