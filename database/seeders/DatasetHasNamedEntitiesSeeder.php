<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetHasNamedEntities;
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
            $namedEntitiesId = NamedEntities::all()->random()->id;

            $datasetHasNamedEntities = DatasetHasNamedEntities::where([
                'dataset_id' => $datasetId,
                'named_entities_id' => $namedEntitiesId,
            ])->first();

            if (!$datasetHasNamedEntities) {
                DatasetHasNamedEntities::create([
                    'dataset_id' => $datasetId,
                    'named_entities_id' => $namedEntitiesId,
                ]);
            }
        }
    }
}
