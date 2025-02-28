<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Publication>
 */
class PublicationFactory extends Factory
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

        return [
            'paper_title' => fake()->words(5, true),
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'year_of_publication' => fake()->year(),
            'paper_doi' => '10.1000/182',
            'publication_type' => fake()->words(1, true),
            'publication_type_mk1' => fake()->words(4, true),
            'journal_name' => fake()->sentence(),
            'abstract' => fake()->paragraph(),
            'url' => fake()->url(),
            'status' => fake()->randomElement([
                Publication::STATUS_ACTIVE,
                Publication::STATUS_ARCHIVED,
                Publication::STATUS_DRAFT,
            ]),
            'team_id' => fake()->randomElement($teams)->id,
            'owner_id' => fake()->randomElement($users)->id,
        ];
    }
}
