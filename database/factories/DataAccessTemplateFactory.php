<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

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
            'team_id' => User::all()->random()->id,
            'user_id' => 1,
            'published' => fake()->randomElement([0, 1]),
            'locked' => 0,
        ];
    }
}
