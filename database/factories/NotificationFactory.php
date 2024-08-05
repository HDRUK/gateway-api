<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notification_type' => fake()->words(5, true),
            'message' => fake()->words(3, true),
            'opt_in' => fake()->boolean(),
            'enabled' => fake()->boolean(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
