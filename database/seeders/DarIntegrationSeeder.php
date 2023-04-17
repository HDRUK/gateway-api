<?php

namespace Database\Seeders;

use App\Models\DarIntegration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DarIntegrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DarIntegration::factory(50)->create();
    }
}
