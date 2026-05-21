<?php

namespace App\Observers;

use App\Jobs\DeindexDataset;
use App\Jobs\IndexDataset;
use App\Jobs\ReindexDataset;
use App\Models\Dataset;
use App\Models\DatasetVersion;

class DatasetObserver
{
    /**
     * Handle the Dataset "created" event.
     */
    public function created(Dataset $dataset): void
    {
        if (!is_null($dataset) && $dataset->status === Dataset::STATUS_ACTIVE && $dataset->active_date === null) {
            $dataset->active_date = now();
            $dataset->save();
        }

        $datasetVersion = DatasetVersion::where([
            'dataset_id' => $dataset->id
        ])->select('id')->first();
        if ($dataset->status === Dataset::STATUS_ACTIVE && !is_null($datasetVersion)) {
            IndexDataset::dispatch($dataset->id);
        }
    }

    /**
     * Handle the Dataset "updating" event.
     */
    public function updating(Dataset $dataset)
    {
        $dataset->prevStatus = (string) $dataset->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Dataset "updated" event.
     */
    public function updated(Dataset $dataset): void
    {
        if (!is_null($dataset) && $dataset->status === Dataset::STATUS_ACTIVE && $dataset->active_date === null) {
            $dataset->active_date = now();
            $dataset->save();
        }

        $prevStatus = $dataset->prevStatus;
        $datasetVersion = DatasetVersion::where([
            'dataset_id' => $dataset->id
        ])->select('id')->first();

        if ($prevStatus === Dataset::STATUS_ACTIVE && $dataset->status !== Dataset::STATUS_ACTIVE) {
            DeindexDataset::dispatch($dataset->id, $dataset->team_id ? (int) $dataset->team_id : null);
        }

        if ($dataset->status === Dataset::STATUS_ACTIVE && !is_null($datasetVersion)) {
            ReindexDataset::dispatch($dataset->id, $dataset->team_id ? (int) $dataset->team_id : null);
        }
    }

    /**
     * Handle the Dataset "updating" event.
     */
    public function deleting(Dataset $dataset)
    {
        $dataset->prevStatus = (string) $dataset->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Dataset "deleted" event.
     */
    public function deleted(Dataset $dataset): void
    {
        $prevStatus = $dataset->prevStatus;

        if ($prevStatus === Dataset::STATUS_ACTIVE) {
            DeindexDataset::dispatch($dataset->id, $dataset->team_id ? (int) $dataset->team_id : null);
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
