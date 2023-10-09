<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetHasNamedEntity;
use App\Models\NamedEntities;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatasetHasNamedEntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $datasetId = Dataset::all()->random()->id;
            $namedEntityId = NamedEntities::all()->random()->id;

            $datasetHasNamedEntities = DatasetHasNamedEntity::where([
                'dataset_id' => $datasetId,
                'named_entity_id' => $namedEntityId,
            ])->first();

            if (!$datasetHasNamedEntities) {
                DatasetHasNamedEntity::create([
                    'dataset_id' => $datasetId,
                    'named_entity_id' => $namedEntityId,
                ]);
            }
        }
    }
}
