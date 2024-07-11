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
            'dataset_version_id' => DatasetVersion::factory(),
            'named_entities_id' => NamedEntities::factory(),
        ];
    }
}
