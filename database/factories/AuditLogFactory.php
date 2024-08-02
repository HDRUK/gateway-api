<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::all()->random()->id,
            'team_id' => Team::all()->random()->id,
            'action_type' => fake()->randomElement(['CREATE', 'UPDATE', 'DELETE', 'UNKNOWN']),
            'action_name' => fake()->randomElement(['Gateway API', 'Translation Service']),
            'description' => fake()->words(10, true),
        ];
    }
}
