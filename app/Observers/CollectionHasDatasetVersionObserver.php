<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Traits\IndexElastic;
use App\Models\Collection;
use App\Models\CollectionHasDatasetVersion;

class CollectionHasDatasetVersionObserver
{
    use IndexElastic;

    /**
     * Handle the CollectionHasDatasetVersion "created" event.
     */
    public function created(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        $this->elasticCollectionHasDatasetVersion($collectionHasDatasetVersion);
    }

    /**
     * Handle the CollectionHasDatasetVersion "updated" event.
     */
    public function updated(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        $this->elasticCollectionHasDatasetVersion($collectionHasDatasetVersion);
    }

    /**
     * Handle the CollectionHasDatasetVersion "deleted" event.
     */
    public function deleted(CollectionHasDatasetVersion $collectionHasDatasetVersion): void
    {
        $this->elasticCollectionHasDatasetVersion($collectionHasDatasetVersion);
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

    public function elasticCollectionHasDatasetVersion(CollectionHasDatasetVersion $collectionHasDatasetVersion)
    {
        $collectionId = $collectionHasDatasetVersion->collection_id;
        $collection = Collection::where([
            'id' => $collectionId,
            'status' => Collection::STATUS_ACTIVE,
        ])->select('id')->first();
        if (!is_null($collection)) {
            $this->indexElasticCollections((int) $collection->id);
        }

        $datasetVersionId = $collectionHasDatasetVersion->dataset_version_id;
        $datasetVersion = DatasetVersion::where([
            'id' => $datasetVersionId
        ])->first();

        if (!is_null($datasetVersion)) {
            $dataset = Dataset::where([
                'id' => $datasetVersion->dataset_id,
                'status' => Dataset::STATUS_ACTIVE,
                ])->first();

            if (!is_null($dataset)) {
                $this->reindexElastic($dataset->id);
            }
        }
    }
}
