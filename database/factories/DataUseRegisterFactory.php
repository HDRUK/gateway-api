<?php

namespace Database\Factories;

use App\Models\TeamHasUser;

use Illuminate\Database\Eloquent\Factories\Factory;

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
        $teamHasUser = TeamHasUser::all()->random();

        $randomString = fake()->words(fake()->randomDigit(), true);
        $shortRandomString = fake()->words(fake()->numberBetween(1, 4), true);

        return [
            'counter' => fake()->randomNumber(4, false),
            'keywords' => [],
            'dataset_ids' => [],
            'gateway_dataset_ids' => [],
            'non_gateway_dataset_ids' => [],
            'gateway_applicants' => [],
            'non_gateway_applicants' => [],
            'funders_and_sponsors' => [],
            'other_approval_committees' => [],
            'gateway_output_tools' => [],
            'gateway_output_papers' => [],
            'non_gateway_outputs' => [],
            'project_title' => $randomString,
            'project_id_text' => $shortRandomString,
            'organisation_name' => $randomString,
            'organisation_sector' => $randomString,
            'lay_summary' => $randomString,
            'latest_approval_date' => fake()->optional()->dateTimeBetween('+0 days'),
            'enabled' => fake()->boolean(),
            'team_id' => $teamHasUser->team_id,
            'user_id' => $teamHasUser->user_id,
            'last_activity' => fake()->optional()->dateTimeBetween('+0 days'),
            'manual_upload' => fake()->boolean(),
            'rejection_reason' => $randomString,
        ];
    }
}
