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
        $ds = Dataset::all();
        return [
            'dataset_id' => fake()->randomElement($ds)->id,
            'metadata' => json_encode($this->getFakeDataset()),
            'version' => fake()->unique()->numberBetween(1, 50),
        ];
    }
}