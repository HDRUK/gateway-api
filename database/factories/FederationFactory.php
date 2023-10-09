<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Federation>
 */
class FederationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->slug();
        return [
            'federation_type' => fake()->lexify('federation-type-????????'),
            'auth_type' => fake()->randomElement(['oauth', 'api_key', 'bearer', 'no_auth']),
            'auth_secret_key' => fake()->unique()->words(2, true),
            'endpoint_baseurl' => fake()->url(),
            'endpoint_datasets' => '/' . $slug,
            'endpoint_dataset' => '/' . $slug . '/{id}',
            'run_time_hour' => fake()->numberBetween(0,23),
            'enabled' => fake()->randomElement([0, 1]),
            'tested' => fake()->randomElement([0, 1]),
        ];
    }
}
