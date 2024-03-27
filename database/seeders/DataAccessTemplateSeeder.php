<?php

namespace Database\Seeders;

use App\Models\DataAccessTemplate;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DataAccessTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataAccessTemplate::factory(1)->create();
    }
}
