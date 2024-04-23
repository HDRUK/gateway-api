<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TypeCategory>
 */
class TypeCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            return [
                'name' => fake()->word(),
                'description' => fake()->text(255),
                'enabled' => fake()->randomElement([0, 1]),
            ];
        ];
    }
}
