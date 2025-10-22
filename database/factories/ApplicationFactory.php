<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppRegistration>
 */
class ApplicationFactory extends Factory
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

        $appId = fake()->regexify('[A-Za-z0-9]{40}');
        $clientId = fake()->regexify('[A-Za-z0-9]{40}');
        $clientSecret = Hash::make($appId . ':' . $clientId . ':' . config('auth.private_salt') . ':' . config('auth.private_salt_2'));

        return [
            'name' => fake()->text(100),
            'app_id' => $appId,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'image_link' => htmlentities(fake()->imageUrl(640, 480, 'animals', true), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'description' => fake()->text(),
            'team_id' => fake()->randomElement($teams)->id,
            'user_id' => fake()->randomElement($users)->id,
            'enabled' => 1,
        ];
    }
}
