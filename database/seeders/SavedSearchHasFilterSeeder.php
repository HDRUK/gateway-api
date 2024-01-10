<?php

namespace Database\Seeders;

use App\Models\Filter;
use App\Models\SavedSearch;
use App\Models\SavedSearchHasFilter;
use Illuminate\Database\Seeder;

class SavedSearchHasFilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 10; $count++) {
            $savedSearchId = SavedSearch::all()->random()->id;
            $filterId = Filter::all()->random()->id;

            $searchHasFilters = SavedSearchHasFilter::where([
                'saved_search_id' => $savedSearchId,
                'filter_id' => $filterId,
            ])->first();

            if (!$searchHasFilters) {
                SavedSearchHasFilter::create([
                    'saved_search_id' => $savedSearchId,
                    'filter_id' => $filterId,
                ]);
            }
        }
    }
}