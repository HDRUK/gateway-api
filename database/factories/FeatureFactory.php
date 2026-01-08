<?php

namespace Database\Factories;

use App\Models\Feature as FeatureModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feature>
 */
class FeatureFactory extends Factory
{
    protected $model = FeatureModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'scope' => null,
            'value' => fake()->boolean(),
            'description' => fake()->sentence(),
        ];
    }
}
