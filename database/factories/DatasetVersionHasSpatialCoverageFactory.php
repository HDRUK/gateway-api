<?php

namespace Database\Factories;

use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\DatasetVersion;
use App\Models\SpatialCoverage;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatasetVersionHasSpatialCoverageFactory extends Factory
{
    protected $model = DatasetVersionHasSpatialCoverage::class;

    public function definition()
    {
        return [
            'dataset_version_id' => DatasetVersion::all()->random()->id,
            'spatial_coverage_id' => SpatialCoverage::all()->random()->id,
        ];
    }
}
