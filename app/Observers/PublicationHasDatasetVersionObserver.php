<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Traits\IndexElastic;
use App\Models\PublicationHasDatasetVersion;

class PublicationHasDatasetVersionObserver
{
    use IndexElastic;

    /**
     * Handle the PublicationHasDatasetVersion "created" event.
     */
    public function created(PublicationHasDatasetVersion $publicationHasDatasetVersion): void
    {
        $datasetVersionId = $publicationHasDatasetVersion->dataset_version_id;
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
     * Handle the PublicationHasDatasetVersion "updated" event.
     */
    public function updated(PublicationHasDatasetVersion $publicationHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the PublicationHasDatasetVersion "deleted" event.
     */
    public function deleted(PublicationHasDatasetVersion $publicationHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the PublicationHasDatasetVersion "restored" event.
     */
    public function restored(PublicationHasDatasetVersion $publicationHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the PublicationHasDatasetVersion "force deleted" event.
     */
    public function forceDeleted(PublicationHasDatasetVersion $publicationHasDatasetVersion): void
    {
        //
    }
}
