<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavedSearch>
 */
class SavedSearchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::all()->random();

        $search_endpoints = [
            'collections',
            'datasets',
            'dur',
            'tools',
        ];

        return [
            'name' => fake()->word(),
            'search_term' => fake()->word(3),
            'search_endpoint' => fake()->randomElement($search_endpoints),
            'enabled' => fake()->randomElement([0, 1]),
            'user_id' => $user['id'],
        ];
    }
}
