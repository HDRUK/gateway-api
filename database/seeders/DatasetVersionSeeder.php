<?php

namespace Database\Seeders;

use Config;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Database\Seeder;
use Tests\Traits\MockExternalApis;

class DatasetVersionSeeder extends Seeder
{
    use MockExternalApis;

    /**
     * Run the database seeds.
     * When GWDM version is 2.1+: seeds metadata from all files in hdruk_41_dummy_data, cycling through them.
     * For any other GWDM version: uses existing logic (factory default via getMetadata() from config).
     */
    public function run(): void
    {
        $datasets = Dataset::all();
        $gwdmVersion = Config::get('metadata.GWDM.version', '2.0');
        $useGwdm21Pool = version_compare($gwdmVersion, '2.1', '>=');

        $gwdm21MetadataPool = $useGwdm21Pool ? $this->loadGwdm21MetadataPool() : [];
        $poolIndex = 0;

        foreach ($datasets as $dataset) {
            $numVersions = rand(1, 5);

            for ($version = 1; $version <= $numVersions; $version++) {
                $attributes = [
                    'dataset_id' => $dataset->id,
                    'provider_team_id' => $dataset->team_id,
                    'version' => $version,
                ];

                if ($useGwdm21Pool && $poolIndex < count($gwdm21MetadataPool)) {
                    $attributes['metadata'] = $gwdm21MetadataPool[$poolIndex];
                    $poolIndex++;
                } elseif ($useGwdm21Pool) {
                    $attributes['metadata'] = $this->getMetadata();
                    $poolIndex++;
                }

                DatasetVersion::factory()->create($attributes);
            }
        }
    }

    /**
     * Load all GWDM 2.1 metadata JSON files from hdruk_41_dummy_data.
     * Files use the same shape as gwdm_v2p0_dataset_min.json (gwdmVersion + metadata).
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadGwdm21MetadataPool(): array
    {
        $dir = base_path('tests/Unit/test_files/hdruk_41_dummy_data');
        $files = glob($dir . '/dataset_*_gwdm21.json');
        sort($files);

        $pool = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $pool[] = $decoded;
                }
            }
        }

        return $pool;
    }
}
