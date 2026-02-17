<?php

namespace App\Observers;

use App\Models\Dur;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Traits\IndexElastic;
use App\Models\DurHasDatasetVersion;

class DurHasDatasetVersionObserver
{
    use IndexElastic;

    /**
     * Handle the DurHasDatasetVersion "created" event.
     */
    public function created(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        $this->elasticDurHasDatasetVersion($durHasDatasetVersion);
    }

    /**
     * Handle the DurHasDatasetVersion "updated" event.
     */
    public function updated(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        $this->elasticDurHasDatasetVersion($durHasDatasetVersion);
    }

    /**
     * Handle the DurHasDatasetVersion "deleted" event.
     */
    public function deleted(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        $this->elasticDurHasDatasetVersion($durHasDatasetVersion);
    }

    /**
     * Handle the DurHasDatasetVersion "restored" event.
     */
    public function restored(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        //
    }

    /**
     * Handle the DurHasDatasetVersion "force deleted" event.
     */
    public function forceDeleted(DurHasDatasetVersion $durHasDatasetVersion): void
    {
        //
    }

    public function elasticDurHasDatasetVersion(DurHasDatasetVersion $durHasDatasetVersion)
    {
        $durId = $durHasDatasetVersion->dur_id;
        $dur = Dur::where([
            'id' => $durId,
            'status' => Dur::STATUS_ACTIVE,
        ])->select('id')->first();

        if (is_null($dur)) {
            return;
        }

        $this->indexElasticDur($dur->id);

        $datasetVersionId = $durHasDatasetVersion->dataset_version_id;
        $datasetVersion = DatasetVersion::where([
            'id' => $datasetVersionId
        ])->select('dataset_id')->first();

        if (!is_null($datasetVersion)) {
            $dataset = Dataset::where([
                'id' => $datasetVersion->dataset_id,
                'status' => Dataset::STATUS_ACTIVE,
            ])->select(['id', 'team_id'])->first();

            if (!is_null($dataset)) {
                $this->reindexElastic($dataset->id);
                if ($dataset->team_id) {
                    $this->reindexElasticDataProviderWithRelations((int) $dataset->team_id, 'dataset');
                }
            }
        }
    }
}
