<?php

use App\DeploymentSteps\DeploymentStep;
use App\Jobs\IndexDataset;
use App\Models\Dataset;
use App\Models\DatasetVersion;

/**
 * Re-apply the 20 CRUK dummy datasets from tests/Unit/test_files/cruk_dummy_data.
 *
 * Needed because 2026_08_04_150237_upsert_cruk_dummy_datasets already ran with
 * fixtures that had invalid GWDM observedNode / primaryGroup values (and missing
 * root identifier). Those fixtures have since been fixed; this step refreshes
 * existing dataset_versions.metadata (and related columns) from the updated files.
 *
 * Only runs in local/dev.
 *
 * Idempotent on metadata.required.gatewayId (datasets.pid) and dataset_id +
 * version = 1. Soft-deleted matches are restored.
 */
return new class () extends DeploymentStep {
    public function handle(): void
    {
        if (!app()->environment(['local', 'dev'])) {
            $this->warn('Skipping CRUK dummy refresh: only runs in local/dev environments.');

            return;
        }

        $files = glob(base_path('tests/Unit/test_files/cruk_dummy_data/dataset_*.json')) ?: [];
        sort($files, SORT_NATURAL);

        if ($files === []) {
            $this->warn('No CRUK dummy data fixtures found; nothing to refresh.');

            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($files as $path) {
            $metadata = json_decode(file_get_contents($path), true);
            $gatewayId = $metadata['metadata']['required']['gatewayId'] ?? null;

            if (!$gatewayId) {
                $this->warn('Skipping ' . basename($path) . ': missing metadata.required.gatewayId');
                $skipped++;

                continue;
            }

            $summary = $metadata['metadata']['summary'] ?? [];
            $gwdmVersion = $metadata['gwdmVersion'] ?? '2.0';

            $dataset = Dataset::withTrashed()->where('pid', $gatewayId)->first();

            if ($dataset) {
                if ($dataset->trashed()) {
                    $dataset->restore();
                }

                $dataset->fill([
                    'partner_context' => 'CRUK',
                    'status' => Dataset::STATUS_ACTIVE,
                ])->save();

                $updated++;
            } else {
                $dataset = Dataset::create(array_merge(
                    Dataset::factory()->raw(),
                    [
                        'pid' => $gatewayId,
                        'partner_context' => 'CRUK',
                        'status' => Dataset::STATUS_ACTIVE,
                    ]
                ));

                $created++;
            }

            $version = DatasetVersion::withTrashed()
                ->where('dataset_id', $dataset->id)
                ->where('version', 1)
                ->first();

            $versionAttributes = [
                'metadata' => $metadata,
                'title' => $summary['title'] ?? null,
                'short_title' => $summary['shortTitle'] ?? $summary['title'] ?? null,
                'gwdm_version' => $gwdmVersion,
            ];

            if ($version) {
                if ($version->trashed()) {
                    $version->restore();
                }

                $version->fill($versionAttributes);
                $version->provider_team_id = $dataset->team_id;
                $version->save();
            } else {
                $version = new DatasetVersion(array_merge($versionAttributes, [
                    'dataset_id' => $dataset->id,
                    'version' => 1,
                ]));
                $version->provider_team_id = $dataset->team_id;
                $version->save();
            }

            try {
                IndexDataset::dispatchSync($dataset->id);
            } catch (\Throwable $e) {
                $this->warn(
                    "Dataset {$dataset->id}: search index skipped ({$e->getMessage()})"
                );
            }
        }

        $this->info(
            "CRUK dummy refresh complete: {$created} dataset(s) created, "
            . "{$updated} updated, {$skipped} fixture(s) skipped."
        );
    }
};
