<?php

namespace App\Observers;

use App\Http\Traits\IndexElastic;
use App\Jobs\DeindexDataset;
use App\Jobs\ExtractPublicationsFromMetadata;
use App\Jobs\ExtractToolsFromMetadata;
use App\Jobs\IndexDataset;
use App\Jobs\ReindexDataset;
use App\Models\Dataset;
use App\Models\DatasetVersion;

class DatasetVersionObserver
{
    use IndexElastic;

    /**
     * Handle the DatasetVersion "created" event.
     */
    public function created(DatasetVersion $datasetVersion): void
    {
        $this->elasticDatasetVersion($datasetVersion, 'created');
    }

    /**
     * Handle the DatasetVersion "updated" event.
     */
    public function updated(DatasetVersion $datasetVersion): void
    {
        $this->elasticDatasetVersion($datasetVersion, 'updated');
    }

    /**
     * Handle the DatasetVersion "deleted" event.
     */
    public function deleted(DatasetVersion $datasetVersion): void
    {
        $this->elasticDatasetVersion($datasetVersion, 'deleted');
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

    public function elasticDatasetVersion(DatasetVersion $datasetVersion, string $action)
    {
        $datasetId = $datasetVersion->dataset_id;
        $dataset = Dataset::where([
            'id' => $datasetId,
        ])->select('id', 'status', 'team_id')->first();

        if (is_null($dataset)) {
            return;
        }

        if ($datasetVersion->active_date === null && $dataset->status === Dataset::STATUS_ACTIVE) {
            $datasetVersion->active_date = now();
            $datasetVersion->save();
        }

        switch ($action) {
            case 'created':
                if ($dataset->status === Dataset::STATUS_ACTIVE) {
                    ExtractPublicationsFromMetadata::dispatch($datasetVersion->id);
                    ExtractToolsFromMetadata::dispatch($datasetVersion->id);
                    IndexDataset::dispatch($datasetId);
                }
                break;

            case 'updated':
                if ($dataset->status === Dataset::STATUS_ACTIVE) {
                    ExtractPublicationsFromMetadata::dispatch($datasetVersion->id);
                    ExtractToolsFromMetadata::dispatch($datasetVersion->id);
                    ReindexDataset::dispatch($dataset->id, $dataset->team_id ? (int) $dataset->team_id : null);
                }

                break;
            case 'deleted':
                DeindexDataset::dispatch($dataset->id, $dataset->team_id ? (int) $dataset->team_id : null);
                break;
        }
    }
}
