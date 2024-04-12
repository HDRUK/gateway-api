<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Collection;
use App\Models\CollectionHasDur;
use App\Models\Dur;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CollectionHasDurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $collectionId = Collection::all()->random()->id;
            $userId = User::all()->random()->id;
            $durId = Dur::all()->random()->id;

            $collectionHasDur = CollectionHasDur::where([
                'collection_id' => $collectionId,
                'dur_id' => $durId,
            ])->first();

            if (!$collectionHasDur) {
                CollectionHasDur::create([
                    'collection_id' => $collectionId,
                    'dur_id' => $durId,
                    'user_id' => $userId,
                ]);
            }
        }
    }
}
