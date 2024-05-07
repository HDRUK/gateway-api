<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Collection;
use App\Models\Publication;
use Illuminate\Database\Seeder;
use App\Models\CollectionHasPublication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CollectionHasPublicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $collectionId = Collection::all()->random()->id;
            $userId = User::all()->random()->id;
            $publicationId = Publication::all()->random()->id;

            $collectionHasPublication = CollectionHasPublication::where([
                'collection_id' => $collectionId,
                'publication_id' => $publicationId,
            ])->first();

            if (!$collectionHasPublication) {
                CollectionHasPublication::create([
                    'collection_id' => $collectionId,
                    'publication_id' => $publicationId,
                    'user_id' => $userId,
                ]);
            }
        }
    }
}
