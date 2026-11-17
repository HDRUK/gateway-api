<?php

namespace App\Observers;

use App\Jobs\ExtractProjectGrantsFromMetadata;
use App\Jobs\ExtractPublicationsFromMetadata;
use App\Jobs\ExtractToolsFromMetadata;
use App\Jobs\SyncDatasetVariables;
use App\Models\Dataset;
use App\Models\DatasetVersion;

class DatasetVersionObserver
{
    /**
     * Handle the DatasetVersion "created" event.
     */
    public function created(DatasetVersion $datasetVersion): void
    {
        $this->handleDatasetVersionChange($datasetVersion, extractLinks: true);
    }

    /**
     * Handle the DatasetVersion "updated" event.
     */
    public function updated(DatasetVersion $datasetVersion): void
    {
        $this->handleDatasetVersionChange($datasetVersion, extractLinks: true);
    }

    /**
     * Handle the DatasetVersion "deleted" event.
     */
    public function deleted(DatasetVersion $datasetVersion): void
    {
        $this->handleDatasetVersionChange($datasetVersion, extractLinks: false);
    }

    private function handleDatasetVersionChange(DatasetVersion $datasetVersion, bool $extractLinks): void
    {
        $dataset = Dataset::where([
            'id' => $datasetVersion->dataset_id,
        ])->select('id', 'status', 'team_id')->first();

        if (is_null($dataset)) {
            return;
        }

        if ($datasetVersion->active_date === null && $dataset->status === Dataset::STATUS_ACTIVE) {
            $datasetVersion->active_date = now();
            $datasetVersion->save();
        }

        if ($extractLinks && $dataset->status === Dataset::STATUS_ACTIVE) {
            ExtractProjectGrantsFromMetadata::dispatch($datasetVersion->id);
            ExtractPublicationsFromMetadata::dispatch($datasetVersion->id);
            ExtractToolsFromMetadata::dispatch($datasetVersion->id);
        }

        SyncDatasetVariables::dispatch((int) $dataset->id);
    }
}
