<?php

namespace App\Observers;

use App\Models\Dur;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Http\Traits\IndexElastic;
use App\Models\DurHasDatasetVersion;

class DurObserver
{
    use IndexElastic;

    /**
     * Handle the Dur "created" event.
     */
    public function created(Dur $dur): void
    {
        $this->linkDatasets($dur->id);
        if ($dur->status === Dur::STATUS_ACTIVE) {
            $this->indexElasticDur($dur->id);
            if ($dur->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dur->team_id, 'dataset');
            }
        }
    }

    /**
     * Handle the Dur "updating" event.
     */
    public function updating(Dur $dur)
    {
        $dur->prevStatus = $dur->getOriginal('status'); // 'status' before updating
    }

    /**
     * Handle the Dur "updated" event.
     */
    public function updated(Dur $dur): void
    {
        $this->linkDatasets($dur->id);
        $prevStatus = $dur->prevStatus;

        if ($prevStatus === Dur::STATUS_ACTIVE && $dur->status !== Dur::STATUS_ACTIVE) {
            $this->deleteDurFromElastic($dur->id);
            if ($dur->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dur->team_id, 'dataset');
            }
        }

        if ($dur->status === Dur::STATUS_ACTIVE) {
            $this->indexElasticDur($dur->id);
            if ($dur->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dur->team_id, 'dataset');
            }
        }
    }

    /**
     * Handle the Collection "deleting" event.
     */
    public function deleting(Dur $dur)
    {
        $dur->prevStatus = $dur->getOriginal('status'); // 'status' before deleting
    }

    /**
     * Handle the Dur "deleted" event.
     */
    public function deleted(Dur $dur): void
    {
        $prevStatus = $dur->prevStatus;

        if ($prevStatus === Dur::STATUS_ACTIVE) {
            $this->deleteDurFromElastic($dur->id);
            if ($dur->team_id) {
                $this->reindexElasticDataProviderWithRelations((int) $dur->team_id, 'dataset');
            }
        }
    }

    /**
     * Handle the Dur "restored" event.
     */
    public function restored(Dur $dur): void
    {
        //
    }

    /**
     * Handle the Dur "force deleted" event.
     */
    public function forceDeleted(Dur $dur): void
    {
        //
    }

    private function linkDatasets(int $durId): void
    {
        $dur = Dur::findOrFail($durId);
        $nonGatewayDatasets = $dur['non_gateway_datasets'];
        $unmatched = array();
        foreach ($nonGatewayDatasets as $d) {
            // Try to match on url
            if (str_contains($d, env('GATEWAY_URL'))) {
                $exploded = explode('/', $d);
                $datasetId = (int) end($exploded);
                $dataset = Dataset::where('id', $datasetId)->first();
                if ($dataset) {
                    $dvID = $dataset->latestVersionID($datasetId);
                    DurHasDatasetVersion::create([
                        'dur_id' => $durId,
                        'dataset_version_id' => $dvID
                    ]);
                    continue;
                }
            }

            // Try to string match on dataset titles
            // BES 30/10/24: skip this attempt if running on an sqlite DB_CONNECTION
            // because JSON_UNQUOTE does not exist in sqlite
            // and the alternative of grabbing and searching all the metadata is computationally infeasible
            if (env('DB_CONNECTION') !== 'sqlite') {
                $dCleaned = trim($d);
                $datasetVersion = DatasetVersion::whereRaw(
                    "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.shortTitle')) LIKE LOWER(?)",
                    ["%$dCleaned%"]
                )->latest('version')->first();
                if ($datasetVersion) {
                    DurHasDatasetVersion::create([
                        'dur_id' => $durId,
                        'dataset_version_id' => $datasetVersion->id
                    ]);
                    continue;
                }
            }

            // If no match above, assume $d is a non gateway dataset
            $unmatched[] = $d;
        }

        $dur->update([
            'non_gateway_datasets' => $unmatched
        ]);
    }
}
