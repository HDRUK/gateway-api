<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataAccessApplicationAnswer>
 */
class DataAccessApplicationAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::all()->random();

        return [
            'question_id' => 1,
            'application_id' => 1,
            'answer' => '{}',
            'contributor_id' => $user->id,
        ];
    }
}
