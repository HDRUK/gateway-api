<?php

namespace App\Observers;

use App\Models\Dataset;
use App\Http\Traits\IndexElastic;
use App\Models\DatasetVersion;

class DatasetObserver
{
    use IndexElastic;

    /**
     * Handle the Dataset "created" event.
     */
    public function created(Dataset $dataset): void
    {
        $datasetVersion = DatasetVersion::where([
            'dataset_id' => $dataset->id
        ])->select('id')->first();
        if ($dataset->status === Dataset::STATUS_ACTIVE && !is_null($datasetVersion)) {
            $this->reindexElastic($dataset->id);
            if ($dataset->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dataset->team_id, 'dataset');
            }
        }
    }

    /**
     * Handle the Dataset "updating" event.
     */
    public function updating(Dataset $dataset)
    {
        $dataset->prevStatus = $dataset->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Dataset "updated" event.
     */
    public function updated(Dataset $dataset): void
    {
        $prevStatus = $dataset->prevStatus;
        $datasetVersion = DatasetVersion::where([
            'dataset_id' => $dataset->id
        ])->select('id')->first();

        if ($prevStatus === Dataset::STATUS_ACTIVE && $dataset->status !== Dataset::STATUS_ACTIVE) {
            $this->deleteDatasetFromElastic($dataset->id);
            if ($dataset->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dataset->team_id, 'dataset');
            }

        }

        if ($dataset->status === Dataset::STATUS_ACTIVE && !is_null($datasetVersion)) {
            $this->reindexElastic($dataset->id);
            if ($dataset->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dataset->team_id, 'dataset');
            }

        }
    }

    /**
     * Handle the Dataset "updating" event.
     */
    public function deleting(Dataset $dataset)
    {
        $dataset->prevStatus = $dataset->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Dataset "deleted" event.
     */
    public function deleted(Dataset $dataset): void
    {
        $prevStatus = $dataset->prevStatus;

        if ($prevStatus === Dataset::STATUS_ACTIVE) {
            $this->deleteDatasetFromElastic($dataset->id);
            if ($dataset->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dataset->team_id, 'dataset');
            }

        }
    }

    /**
     * Handle the Dataset "restored" event.
     */
    public function restored(Dataset $dataset): void
    {
        //
    }

    /**
     * Handle the Dataset "force deleted" event.
     */
    public function forceDeleted(Dataset $dataset): void
    {
        //
    }
}
