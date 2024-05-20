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
        DataProvider::factory(3)->create();

        DataProviderHasTeam::create([
            'data_provider_id' => 1,
            'team_id' => 1,
        ]);
    }
}
