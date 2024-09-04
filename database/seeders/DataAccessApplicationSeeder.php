<?php

namespace Database\Seeders;

use App\Models\DataAccessApplication;
use Illuminate\Database\Seeder;

class DataAccessApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataAccessApplication::factory(1)->create();
    }
}
