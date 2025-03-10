<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use App\Models\Team;

class DataProviderCollsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataProviderColl::factory(3)->create();

        $dataProviderColls = DataProviderColl::all();

        foreach ($dataProviderColls as $dataProviderColl) {
            DataProviderCollHasTeam::create([
                'data_provider_coll_id' => $dataProviderColl->id,
                'team_id' => Team::all()->random()->id,
            ]);
        }

    }
}
