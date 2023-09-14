<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Federation;
use Illuminate\Database\Seeder;
use App\Models\TeamHasFederation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamHasFederationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 200; $count++) {
            $teamId = Team::all()->random()->id;
            $federationId = Federation::all()->random()->id;

            $teamHasFederation = TeamHasFederation::where([
                'federation_id' => $federationId,
            ])->first();

            if (!$teamHasFederation) {
                TeamHasFederation::create([
                    'team_id' => $teamId,
                    'federation_id' => $federationId,
                ]);
            }
        }
    }
}
