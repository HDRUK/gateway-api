<?php

namespace Database\Factories;
use App\Models\Team;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnquiryThread>
 */
class EnquiryThreadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $team_id = Team::all()->random()->id;
        $user_id = User::all()->random()->id;
        $title = fake()->sentence();

        return [
            'team_id' => $team_id,
            'user_id' => $user_id,
            'title' => $title,
        ];
    }
}
