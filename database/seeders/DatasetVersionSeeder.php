<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Database\Seeder;
use Tests\Traits\MockExternalApis;

class DatasetVersionSeeder extends Seeder
{
    use MockExternalApis;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datasets = Dataset::all();

        foreach ($datasets as $dataset) {
            // Generate a random number of dataset versions for each dataset
            $numVersions = rand(1, 5);

            for ($version = 1; $version <= $numVersions; $version++) {
                DatasetVersion::factory()->create([
                    'dataset_id' => $dataset->id,
                    'provider_team_id' => $dataset->team_id,
                    'version' => $version, // Ensure the version increments
                ]);
            }
        }
    }
}
