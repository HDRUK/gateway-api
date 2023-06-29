<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppRegistration>
 */
class AppRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teams = Team::all();
        $users = User::all();

        return [
            'name' => fake()->text(100),
            'app_id' => fake()->regexify('[A-Za-z0-9]{32}'),
            'client_id' => fake()->regexify('[A-Za-z0-9]{32}'),
            'logo' => htmlentities(fake()->imageUrl(640, 480, 'animals', true), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'description' => fake()->text(),
            'team_id' => fake()->randomElement($teams)->id,
            'user_id' => fake()->randomElement($users)->id,
            'enabled' => fake()->randomElement([0, 1]),
        ];
    }
}
