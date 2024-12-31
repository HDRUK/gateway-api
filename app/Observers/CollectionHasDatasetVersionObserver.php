<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Traits\IndexElastic;
use App\Models\CollectionHasDatasetVersion;

class CollectionHasDatasetVersionObserver
{
    use IndexElastic;

    /**
     * Handle the CollectionHasDatasetVersion "created" event.
     */
    public function created(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        $datasetVersionId = $collectionHasDatasetVersion->dataset_version_id;
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
     * Handle the CollectionHasDatasetVersion "updated" event.
     */
    public function updated(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the CollectionHasDatasetVersion "deleted" event.
     */
    public function deleted(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the CollectionHasDatasetVersion "restored" event.
     */
    public function restored(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the CollectionHasDatasetVersion "force deleted" event.
     */
    public function forceDeleted(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        //
    }
}
