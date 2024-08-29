<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Dur;
use App\Models\DurHasDatasetVersion;
use App\Models\Dataset;
use Illuminate\Database\Seeder;

class DurHasDatasetVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure there are enough Dur, User, and Dataset records
        if (Dur::count() == 0) {
            throw new \Exception('Ensure that there are enough Dur records before seeding DurHasDatasetVersion.');
        }
        if (User::count() == 0) {
            throw new \Exception('Ensure that there are enough User records before seeding DurHasDatasetVersion.');
        }
        if (Dataset::count() == 0) {
            throw new \Exception('Ensure that there are enough Dataset records before seeding DurHasDatasetVersion.');
        }

        for ($count = 1; $count <= 50; $count++) {
            $durId = Dur::all()->random()->id;
            $userId = User::all()->random()->id;
            $dataset = Dataset::all()->random();
            $datasetVersionId = $dataset->latestVersion()->id;

            $durHasDataset = DurHasDatasetVersion::where([
                'dur_id' => $durId,
                'dataset_version_id' => $datasetVersionId,
            ])->first();

            if (!$durHasDataset) {
                DurHasDatasetVersion::create([
                    'dur_id' => $durId,
                    'dataset_version_id' => $datasetVersionId,
                    'user_id' => $userId,
                    'reason' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'is_locked' => fake()->randomElement([0, 1])
                ]);
            }
        }
    }
}
