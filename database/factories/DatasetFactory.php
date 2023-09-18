<?php

namespace Database\Factories;

use Config;
use App\Models\TeamHasUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Filter>
 */
class DatasetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teamHasUser = TeamHasUser::all()->random();

        return [
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'label' => fake()->words(fake()->randomDigit(), true),
            'short_description' => fake()->words(fake()->randomDigit(), true),
        ];
    }
}