<?php

namespace Database\Seeders;

use App\Models\DatasetVersion;
use App\Models\User;
use App\Models\Collection;
use App\Models\CollectionHasDatasetVersion;
use App\Models\Dataset;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CollectionHasDatasetVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $collectionId = Collection::all()->random()->id;
            $userId = User::all()->random()->id;
            $datasetVersionId = DatasetVersion::all()->random()->id;

            $collectionHasDataset = CollectionHasDatasetVersion::where([
                'collection_id' => $collectionId,
                'dataset_version_id' => $datasetVersionId,
            ])->first();

            if (!$collectionHasDataset) {
                CollectionHasDatasetVersion::create([
                    'collection_id' => $collectionId,
                    'dataset_version_id' => $datasetVersionId,
                    'user_id' => $userId,
                ]);
            }
        }
    }
}
