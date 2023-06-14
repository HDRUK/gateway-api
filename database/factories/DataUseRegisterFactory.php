<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;
        $users = User::all();

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataUseRegister>
 */
class DataUseRegisterFactory extends Factory
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

        $randomString = fake()->words(fake()->randomDigit(), true);

        return [
            'counter' => fake()->randomNumber(4, false),
            // 'keywords' => '',
            // 'dataset_ids' => '',
            // 'gateway_dataset_ids' => '',
            // 'non_gateway_dataset_ids' => '',
            // 'gateway_applicants' => '',
            // 'non_gateway_applicants' => '',
            // 'funders_and_sponsors' => '',
            // 'other_approval_committees' => '',
            // 'gateway_output_tools' => '',
            // 'gateway_output_papers' => '',
            // 'non_gateway_outputs' => '',
            // 'project_title' => $randomString,
            // 'project_id_text' => $randomString,
            // 'organisation_name' => $randomString,
            // 'organisation_sector' => $randomString,
            // 'lay_summary' => $randomString,
            // 'latest_approval_date' => fake()->dateTime(),
            // 'enabled' => fake()->numberBetween(0, 1),
            'team_id' => fake()->randomElement($teams)->id,
            'user_id' => fake()->randomElement($users)->id,
            // 'last_activity' => fake()->dateTime(),
            // 'manual_upload' => fake()->numberBetween(0, 1),
            // 'rejection_reason' => $randomString,
        ];
    }
}
