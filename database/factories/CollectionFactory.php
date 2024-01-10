<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => htmlentities(fake()->imageUrl(640, 480, 'animals', true), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
        ];
    }
}
