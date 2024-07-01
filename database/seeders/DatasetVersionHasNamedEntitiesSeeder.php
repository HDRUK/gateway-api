<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasNamedEntities;
use App\Models\NamedEntities;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatasetHasNamedEntitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $datasetId = Dataset::all()->random()->id;
            $datasetVersionId = DatasetVersion::where('dataset_id', $datasetId)->first();
            $namedEntitiesId = NamedEntities::all()->random()->id;

            $datasetHasNamedEntities = DatasetVersionHasNamedEntities::where([
                'dataset_id' => $datasetVersionId,
                'named_entities_id' => $namedEntitiesId,
            ])->first();

            if (!$datasetHasNamedEntities) {
                DatasetVersionHasNamedEntities::create([
                    'dataset_id' => $datasetVersionId,
                    'named_entities_id' => $namedEntitiesId,
                ]);
            }
        }
    }
}
