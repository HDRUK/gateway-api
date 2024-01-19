<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\CollectionHasKeyword;
use App\Models\Keyword;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CollectionHasKeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $collectionId = Collection::all()->random()->id;
            $keywordId = Keyword::where(['enabled' => 1])->get()->random()->id;

            $collectionHasKeyword = CollectionHasKeyword::where([
                'collection_id' => $collectionId,
                'keyword_id' => $keywordId,
            ])->first();

            if (!$collectionHasKeyword) {
                CollectionHasKeyword::create([
                    'collection_id' => $collectionId,
                    'keyword_id' => $keywordId,
                ]);
            }
        }
    }
}
