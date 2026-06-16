<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Database\Seeder;

class CRUKDatasetVersionSeeder extends Seeder
{
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
