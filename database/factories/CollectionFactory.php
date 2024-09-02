<?php

namespace Database\Factories;

use Config;
use App\Models\Team;
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
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => Team::all()->random()->id,
            'status' => 'ACTIVE',
        ];
    }
}
