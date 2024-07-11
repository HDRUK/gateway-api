<?php

namespace Database\Factories;

use App\Models\DatasetVersionHasNamedEntities;
use App\Models\DatasetVersion;
use App\Models\NamedEntities;
use Illuminate\Database\Eloquent\Factories\Factory;

class DatasetVersionHasNamedEntitiesFactory extends Factory
{
    protected $model = DatasetVersionHasNamedEntities::class;

    public function definition()
    {
        return [
            'dataset_version_id' => DatasetVersion::all()->random()->id,
            'named_entities_id' => NamedEntities::all()->random()->id,
        ];
    }
}
