<?php

namespace Database\Seeders;

use App\Models\Federation;
use App\Models\FederationJobRun;
use App\Models\TeamHasFederation;
use Illuminate\Database\Seeder;

class FederationJobRunSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Federation::all()->each(function (Federation $federation) {
            $teamHasFederation = TeamHasFederation::where('federation_id', $federation->id)->first();

            if (!$teamHasFederation) {
                return;
            }

            for ($execution = 1; $execution <= 5; $execution++) {
                $jobUuid = fake()->uuid();
                $createdAt = now()->subDays(5 - $execution);
                $datasetCount = fake()->numberBetween(2, 5);
                $failedIndex = fake()->boolean(30) ? fake()->numberBetween(0, $datasetCount - 1) : null;

                for ($dataset = 0; $dataset < $datasetCount; $dataset++) {
                    $factory = FederationJobRun::factory();

                    if ($dataset === $failedIndex) {
                        $factory = $factory->failed();
                    }

                    $run = $factory->create([
                        'team_id' => $teamHasFederation->team_id,
                        'federation_id' => $federation->id,
                        'job_uuid' => $jobUuid,
                    ]);

                    FederationJobRun::where('id', $run->id)->update(['created_at' => $createdAt]);
                }
            }
        });
    }
}
