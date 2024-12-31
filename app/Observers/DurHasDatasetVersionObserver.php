<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Traits\IndexElastic;
use App\Models\DurHasDatasetVersion;

class DurHasDatasetVersionObserver
{
    use IndexElastic;

    /**
     * Handle the DurHasDatasetVersion "created" event.
     */
    public function created(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        $datasetVersionId = $durHasDatasetVersion->dataset_version_id;
        $datasetVersion = DatasetVersion::where([
            'id' => $datasetVersionId
        ])->first();

        if (!is_null($datasetVersion)) {
            $dataset = Dataset::where(['id' => $datasetVersion->dataset_id])->first();

            if (!is_null($dataset) && $dataset->status === 'ACTIVE') {
                $this->reindexElastic($dataset->id);
            }
        }
    }

    /**
     * Handle the DurHasDatasetVersion "updated" event.
     */
    public function updated(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the DurHasDatasetVersion "deleted" event.
     */
    public function deleted(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the DurHasDatasetVersion "restored" event.
     */
    public function restored(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the DurHasDatasetVersion "force deleted" event.
     */
    public function forceDeleted(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        //
    }
}
