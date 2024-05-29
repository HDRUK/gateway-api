<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataProviderColl;
use App\Models\DataProviderCollHasTeam;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DataProviderCollsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataProviderColl::factory(3)->create();

        DataProviderCollHasTeam::create([
            'data_provider_coll_id' => 1,
            'team_id' => 1,
        ]);
    }
}
