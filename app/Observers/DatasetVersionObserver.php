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
        $datasetId = $datasetVersion->dataset_id;
        $dataset = Dataset::where([
            'id' => $datasetId,
        ])->first();

        if (!is_null($dataset) && $dataset->status === Dataset::STATUS_ACTIVE) {
            $this->reindexElastic($dataset->id);
        }
    }

    /**
     * Handle the DatasetVersion "updated" event.
     */
    public function updated(DatasetVersion $datasetVersion): void
    {
        $datasetId = $datasetVersion->dataset_id;
        $dataset = Dataset::where([
            'id' => $datasetId,
        ])->first();

        if (!is_null($dataset) && $dataset->status === Dataset::STATUS_ACTIVE) {
            $this->reindexElastic($dataset->id);
        }
    }

    /**
     * Handle the DatasetVersion "deleted" event.
     */
    public function deleted(DatasetVersion $datasetVersion): void
    {
        $datasetId = $datasetVersion->dataset_id;
        $dataset = Dataset::where([
            'id' => $datasetId,
        ])->first();

        if (!is_null($dataset) && $dataset->status === Dataset::STATUS_ACTIVE) {
            $this->reindexElastic($dataset->id);
        }
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
}
