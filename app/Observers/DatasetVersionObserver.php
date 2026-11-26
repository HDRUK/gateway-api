<?php

namespace App\Observers;

use App\Jobs\ExtractProjectGrantsFromMetadata;
use App\Jobs\ExtractPublicationsFromMetadata;
use App\Jobs\ExtractToolsFromMetadata;
use App\Jobs\SyncDatasetVariables;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Modules\Concerns\HasModules;

class DatasetVersionObserver
{
    use HasModules;

    /**
     * Handle the DatasetVersion "created" event.
     */
    public function created(DatasetVersion $datasetVersion): void
    {
        $this->handleDatasetVersionChange($datasetVersion, extractLinks: true);
        $this->refreshEnrichment($datasetVersion);
    }

    /**
     * Handle the DatasetVersion "updated" event.
     */
    public function updated(DatasetVersion $datasetVersion): void
    {
        $this->handleDatasetVersionChange($datasetVersion, extractLinks: true);

        // Drafts are edited in place (DatasetService::persistMetadataVersion).
        if ($datasetVersion->wasChanged('metadata')) {
            $this->refreshEnrichment($datasetVersion);
        }
    }

    /**
     * Handle the DatasetVersion "deleted" event.
     */
    public function deleted(DatasetVersion $datasetVersion): void
    {
        $this->handleDatasetVersionChange($datasetVersion, extractLinks: false);
    }

    /**
     * Queue enrichment for the dataset once this version is committed
     * (RunModuleEnrichment is afterCommit). Triggered here, not from
     * DatasetObserver, because DatasetService saves the Dataset row before the
     * version that carries the new metadata. Each module's job compares source
     * versions, so an unchanged link costs one cheap check. Off unless the
     * ModuleManager flag is on and a module is enabled for the dataset's
     * partner_context; never calls a source inline and never throws.
     */
    private function refreshEnrichment(DatasetVersion $datasetVersion): void
    {
        $dataset = Dataset::find($datasetVersion->dataset_id);

        if ($dataset !== null) {
            $this->modules($dataset)->refresh();
        }
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
