<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Collection;
use App\Models\CollectionHasDataset;
use App\Models\Dataset;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CollectionHasDatasetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $collectionId = Collection::all()->random()->id;
            $userId = User::all()->random()->id;
            $datasetId = Dataset::all()->random()->id;

            $collectionHasDataset = CollectionHasDataset::where([
                'collection_id' => $collectionId,
                'dataset_id' => $datasetId,
            ])->first();

            if (!$collectionHasDataset) {
                CollectionHasDataset::create([
                    'collection_id' => $collectionId,
                    'dataset_id' => $datasetId,
                    'user_id' => $userId,
                ]);
            }
        }
    }
}
