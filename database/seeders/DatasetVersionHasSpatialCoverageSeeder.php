<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\NamedEntities;
use Database\Factories\DatasetVersionHasNamedEntitiesFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatasetVersionHasSpatialCoverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $datasetVersionId = DatasetVersion::all()->random()->id;
            $spatialCoverageId = NamedEntities::all()->random()->id;

            $datasetHasSpatialCoverage = DatasetVersionHasSpatialCoverage::where([
                'dataset_version_id' => $datasetVersionId,
                'spatial_coverage_id' => $spatialCoverageId,
            ])->first();    

            if (!$datasetHasSpatialCoverage) {
                DatasetVersionHasSpatialCoverage::create([
                    'dataset_version_id' => $datasetVersionId,
                    'spatial_coverage_id' => $spatialCoverageId,
                ]);
            }
        }
    }
}
