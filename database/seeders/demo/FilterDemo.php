<?php

namespace Database\Seeders\Demo;

use App\Models\Filter;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FilterDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Filter::factory(50)->create();
    }
}
