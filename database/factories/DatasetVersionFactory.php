<?php

namespace Database\Factories;

use Config;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

use Tests\Traits\MockExternalApis;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Filter>
 */
class DatasetVersionFactory extends Factory
{
    use MockExternalApis;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dataset = Dataset::all()->random();
        return [
            'dataset_id' => $dataset->id,
            'metadata' => json_encode($this->getMetadata()),
            'version' => fake()->unique()->numberBetween(1, 50),
            'provider_team_id' => $dataset->team_id,
            'application_type' => fake()->word(),
        ];
    }
}