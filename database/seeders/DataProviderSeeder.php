<?php

namespace Database\Seeders;

use App\Models\DataProvider;
use App\Models\DataProviderHasTeam;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DataProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataProvider::factory(1)->create();

        DataProviderHasTeam::create([
            'data_provider_id' => 1,
            'team_id' => 1,
        ]);
    }
}
