<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Models\Publication;
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
        $this->elasticPublicationHasDatasetVersion($publicationHasDatasetVersion);
    }

    /**
     * Handle the PublicationHasDatasetVersion "updated" event.
     */
    public function updated(PublicationHasDatasetVersion $publicationHasDatasetVersion): void
    {
        $this->elasticPublicationHasDatasetVersion($publicationHasDatasetVersion);
    }

    /**
     * Handle the PublicationHasDatasetVersion "deleted" event.
     */
    public function deleted(PublicationHasDatasetVersion $publicationHasDatasetVersion): void
    {
        $this->elasticPublicationHasDatasetVersion($publicationHasDatasetVersion);
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

    public function elasticPublicationHasDatasetVersion(PublicationHasDatasetVersion $publicationHasDatasetVersion)
    {
        $publicationId = $publicationHasDatasetVersion->publication_id;
        $publication = Publication::where([
            'id' => $publicationId,
            'status' => Publication::STATUS_ACTIVE,
        ])->select('id')->first();
        if (!is_null($publication)) {
            $this->indexElasticPublication((int) $publication->id);
        }

        $datasetVersionId = $publicationHasDatasetVersion->dataset_version_id;
        $datasetVersion = DatasetVersion::where([
            'id' => $datasetVersionId
        ])->select('dataset_id')->first();

        if (!is_null($datasetVersion)) {
            $dataset = Dataset::where([
                'id' => $datasetVersion->dataset_id,
                'status' => Dataset::STATUS_ACTIVE,
            ])->select('id')->first();

            if (!is_null($dataset)) {
                $this->reindexElastic($dataset->id);
            }
        }
    }
}
