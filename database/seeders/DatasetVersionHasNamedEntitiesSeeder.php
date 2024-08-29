<?php

namespace Database\Seeders;

use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasNamedEntities;
use App\Models\NamedEntities;
use Illuminate\Database\Seeder;

class DatasetVersionHasNamedEntitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $datasetVersionId = DatasetVersion::all()->random()->id;
            $namedEntitiesId = NamedEntities::all()->random()->id;

            $datasetHasNamedEntities = DatasetVersionHasNamedEntities::where([
                'dataset_version_id' => $datasetVersionId,
                'named_entities_id' => $namedEntitiesId,
            ])->first();

            if (!$datasetHasNamedEntities) {
                DatasetVersionHasNamedEntities::create([
                    'dataset_version_id' => $datasetVersionId,
                    'named_entities_id' => $namedEntitiesId,
                ]);
            }
        }
    }
}
