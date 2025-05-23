<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;

class DatasetVersionHasDatasetVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $linkageTypes = ['isDerivedFrom', 'isPartOf', 'isMemberOf', 'linkedDatasets'];

        for ($count = 1; $count <= 10; $count++) {
            $datasetVersionId1 = DatasetVersion::all()->random()->id;
            $datasetVersionId2 = DatasetVersion::all()->random()->id;

            // Ensure that we are not linking the same dataset version to itself
            if ($datasetVersionId1 == $datasetVersionId2) {
                continue;
            }

            $linkageType = fake()->randomElement($linkageTypes);
            $directLinkage = fake()->randomElement([0, 1]);
            $description = fake()->paragraph();

            $DatasetVersionHasDatasetVersion = DatasetVersionHasDatasetVersion::where([
                'dataset_version_source_id' => $datasetVersionId1,
                'dataset_version_target_id' => $datasetVersionId2,
                'linkage_type' => $linkageType,
            ])->first();

            if (!$DatasetVersionHasDatasetVersion) {
                DatasetVersionHasDatasetVersion::create([
                    'dataset_version_source_id' => $datasetVersionId1,
                    'dataset_version_target_id' => $datasetVersionId2,
                    'linkage_type' => $linkageType,
                    'direct_linkage' => $directLinkage,
                    'description' => $description,
                ]);
            }
        }
    }
}
