<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\DataProvider;

use Illuminate\Database\Seeder;
use App\Models\DataProviderHasTeam;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DataProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataProvider::factory(10)->create();

        for ($count = 1; $count <= 20; $count++) {
            $dataProviderId = DataProvider::all()->random()->id;
            $teamId = Team::all()->random()->id;

            $dataProviderHasTeam = DataProviderHasTeam::where([
                'data_provider_id' => $dataProviderId,
                'team_id' => $teamId,
            ])->first();

            if (!$dataProviderHasTeam) {
                DataProviderHasTeam::create([
                    'data_provider_id' => $dataProviderId,
                    'team_id' => $teamId,
                ]);
            }
        }  
    }
}
