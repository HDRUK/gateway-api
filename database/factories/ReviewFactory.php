<?php

namespace Database\Factories;

use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tool_id' => Tool::all()->random()->id,
            'user_id' => User::all()->random()->id,
            'rating' => fake()->randomElement([1, 2, 3, 4, 5]),
            'review_text' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'review_state' => fake()->randomElement(['active', 'rejected']),
        ];
    }
}
