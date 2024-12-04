<?php

namespace Database\Factories;

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
            'user_id' => 1,
            'locked' => 0,
            'archived' => 0,
            'archived_date' => null,
            'force_required' => fake()->randomElement([0,1]),
            'allow_guidance_override' => fake()->randomElement([0,1])
        ];
    }
}
