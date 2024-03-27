<?php

namespace Database\Factories;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionBank>
 */
class QuestionBankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => 1,
            'user_id' => 1,
            'team_id' => 1,
            'default' => fake()->randomElement([01, 1]),
            'locked' => fake()->randomElement([0, 1]),
            'required' => fake()->randomElement([0, 1]),
            'question_json' => '{}',
        ];
    }
}
