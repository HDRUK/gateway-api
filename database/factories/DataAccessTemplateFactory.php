<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataAccessTemplate>
 */
class DataAccessTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => 1,
            'user_id' => 1,
            'published' => fake()->randomElement([0, 1]),
            'locked' => fake()->randomElement([0, 1]),
        ];
    }
}
