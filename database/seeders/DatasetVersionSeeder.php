<?php

namespace Database\Seeders;

use App\Models\DatasetVersion;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatasetVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DatasetVersion::factory()->count(10)->create();
    }
}