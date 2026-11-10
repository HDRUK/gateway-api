<?php

namespace Database\Seeders;

use App\Jobs\IndexDataset;
use App\Jobs\LinkageExtraction;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\Gwdm\SourceSchema;
use Illuminate\Database\Seeder;

/**
 * Seeds one sample PRUK dataset and one CROCOD trial case study from
 * tests/Unit/test_files/{pruk,crocod}_dummy_data, stored as GWDM 2.3.
 *
 * The fixtures are TRASER output (PRUK 1.0.0 / CROCOD 1.0.0 -> GWDM 2.3), so
 * this stores them directly rather than calling TRASER.
 *
 * Idempotent on metadata.required.gatewayId (datasets.pid) and dataset_id +
 * version = 1. Soft-deleted matches are restored.
 */
class PrukCrocodSampleSeeder extends Seeder
{
    private const SAMPLES = [
        ['dir' => 'pruk_dummy_data',   'partner' => 'PRUK',   'model' => 'PRUK',   'version' => '1.0.0'],
        ['dir' => 'crocod_dummy_data', 'partner' => 'CROCOD', 'model' => 'CROCOD', 'version' => '1.0.0'],
    ];

    public function run(): void
    {
        foreach (self::SAMPLES as $sample) {
            $files = glob(base_path("tests/Unit/test_files/{$sample['dir']}/dataset_*.json")) ?: [];
            sort($files, SORT_NATURAL);

            foreach ($files as $path) {
                $this->seedFile($path, $sample);
            }
        }
    }

    private function seedFile(string $path, array $sample): void
    {
        $envelope = json_decode(file_get_contents($path), true);
        $gatewayId = $envelope['metadata']['required']['gatewayId'] ?? null;

        if (!$gatewayId) {
            $this->command?->warn('Skipping ' . basename($path) . ': missing metadata.required.gatewayId');

            return;
        }

        $summary = $envelope['metadata']['summary'] ?? [];

        $dataset = Dataset::withTrashed()->where('pid', $gatewayId)->first();

        if ($dataset) {
            if ($dataset->trashed()) {
                $dataset->restore();
            }

            $dataset->fill([
                'partner_context' => $sample['partner'],
                'status' => Dataset::STATUS_ACTIVE,
            ])->save();
        } else {
            $dataset = Dataset::create(array_merge(
                Dataset::factory()->raw(),
                [
                    'pid' => $gatewayId,
                    'partner_context' => $sample['partner'],
                    'status' => Dataset::STATUS_ACTIVE,
                    // Not GMI: GMI rows are always recorded as HDRUK source schema.
                    'create_origin' => Dataset::ORIGIN_MANUAL,
                ]
            ));
        }

        $versionAttributes = array_merge(
            SourceSchema::declared($sample['model'], $sample['version'])->toColumns(),
            [
                'metadata' => $envelope,
                'title' => $summary['title'] ?? null,
                'short_title' => $summary['shortTitle'] ?? $summary['title'] ?? null,
                // Set explicitly: the column default is '2.2', not the envelope's version.
                'gwdm_version' => $envelope['gwdmVersion'] ?? '2.3',
            ]
        );

        $version = DatasetVersion::withTrashed()
            ->where('dataset_id', $dataset->id)
            ->where('version', 1)
            ->first();

        if ($version) {
            if ($version->trashed()) {
                $version->restore();
            }

            $version->fill($versionAttributes);
        } else {
            $version = new DatasetVersion(array_merge($versionAttributes, [
                'dataset_id' => $dataset->id,
                'version' => 1,
            ]));
        }

        $version->provider_team_id = $dataset->team_id;
        $version->save();

        // The observer does not run linkage extraction, so waveOf and the other
        // datasetLinkage entries only become junction rows if it is run here.
        LinkageExtraction::dispatchSync((string) $dataset->id, (string) $version->id);

        try {
            IndexDataset::dispatchSync($dataset->id);
        } catch (\Throwable $e) {
            $this->command?->warn("Dataset {$dataset->id}: search index skipped ({$e->getMessage()})");
        }

        $this->command?->info("{$sample['partner']} sample seeded: dataset {$dataset->id} ({$gatewayId})");
    }
}
