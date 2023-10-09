<?php

namespace Database\Seeders;

use App\Models\NamedEntities;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NamedEntitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NamedEntities::factory(20)->create();
    }
}
