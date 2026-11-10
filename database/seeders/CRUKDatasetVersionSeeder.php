<?php

namespace Database\Seeders;

use App\Services\Gwdm\SourceSchema;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Database\Seeder;

class CRUKDatasetVersionSeeder extends Seeder
{
    private const CRUK_SCHEMA_MODEL = 'CRUK';
    private const CRUK_SCHEMA_VERSION = '1.0.0';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $files = glob(base_path('tests/Unit/test_files/cruk_dummy_data/dataset_*.json'));
        sort($files, SORT_NATURAL);

        $datasets = Dataset::where('partner_context', 'CRUK')->orderBy('id')->get();

        foreach ($datasets as $index => $dataset) {
            if (!isset($files[$index])) {
                break;
            }

            $metadata = json_decode(file_get_contents($files[$index]), true);
            $gatewayId = $metadata['metadata']['required']['gatewayId'] ?? null;

            if ($gatewayId) {
                $dataset->update(['pid' => $gatewayId]);
            }

            $summary = $metadata['metadata']['summary'] ?? [];

            DatasetVersion::create([
                // CRUK submissions are in CRUK 1.0.0, the only CRUK version
                // TRASER can translate; GMI pull ingestion is HDRUK-only.
                ...(
                    $dataset->create_origin === Dataset::ORIGIN_GMI
                    ? SourceSchema::gmi()
                    : SourceSchema::declared(self::CRUK_SCHEMA_MODEL, self::CRUK_SCHEMA_VERSION)
                )->toColumns(),
                'dataset_id' => $dataset->id,
                'metadata' => $metadata,
                'version' => 1,
                'title' => $summary['title'] ?? null,
                'short_title' => $summary['shortTitle'] ?? $summary['title'] ?? null,
                'provider_team_id' => $dataset->team_id,
            ]);
        }
    }
}
