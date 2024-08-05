<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionBankVersion>
 */
class QuestionBankVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'default' => fake()->randomElement([0, 1]),
            'required' => fake()->randomElement([0, 1]),
            'question_json' => '{}',
        ];
    }
}
