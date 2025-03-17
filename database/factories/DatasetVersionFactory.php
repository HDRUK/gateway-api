<?php

namespace Database\Factories;

use App\Models\Dataset;
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
            'version' => fake()->numberBetween(1, 100),
            'provider_team_id' => $dataset->team_id,
            'application_type' => fake()->word(),
        ];
    }
}
