<?php

namespace Database\Factories;

use Config;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Filter>
 */
class FilterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(Config::get('filters.types')),
            'value' => fake()->words(fake()->randomDigit(), true),
            'keys' => fake()->randomElement(Config::get('filters.keys')),
            'enabled' => fake()->boolean(),
        ];
    }
}
