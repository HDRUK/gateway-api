<?php

namespace Database\Seeders;

use App\Models\SpatialCoverage;
use Illuminate\Database\Seeder;

class SpatialCoverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            'England',
            'Northern Ireland',
            'Scotland',
            'Wales',
            'Rest of the world'
        ];

        foreach ($regions as $region) {
            SpatialCoverage::create([
                'region' => $region,
                'enabled' => true,
            ]);
        }
    }
}
