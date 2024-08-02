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
            'auth_type' => fake()->randomElement(['API_KEY', 'BEARER', 'NO_AUTH']),
            'auth_secret_key_location' => null,
            'endpoint_baseurl' => fake()->url(),
            'endpoint_datasets' => '/' . $slug,
            'endpoint_dataset' => '/' . $slug . '/{id}',
            'run_time_hour' => fake()->numberBetween(0, 23),
            'enabled' => 0,
            'tested' => fake()->randomElement([0, 1]),
        ];
    }
}
