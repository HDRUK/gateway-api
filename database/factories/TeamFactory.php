<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'enabled' => fake()->boolean(),
            'allows_messaging' => fake()->boolean(),
            'workflow_enabled' => fake()->boolean(),
            'access_requests_management' => fake()->boolean(),
            'uses_5_safes' => fake()->boolean(),
            'is_admin' => fake()->boolean(),
            'member_of' => fake()->randomNumber(4, false),
            'contact_point' => fake()->email(),
            'application_form_updated_by' => fake()->words(2, true),
            'application_form_updated_on' => fake()->dateTime(),
        ];
    }
}
