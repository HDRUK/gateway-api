<?php

namespace Database\Seeders;

use App\Models\SavedSearch;
use Illuminate\Database\Seeder;

class SavedSearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SavedSearch::factory(50)->create();
    }
}
