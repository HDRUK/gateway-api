<?php

namespace Database\Factories;

use Config;
use App\Http\Enums\TeamMemberOf;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'enabled' => fake()->boolean(),
            'allows_messaging' => fake()->boolean(),
            'workflow_enabled' => fake()->boolean(),
            'access_requests_management' => fake()->boolean(),
            'uses_5_safes' => fake()->boolean(),
            'is_admin' => fake()->boolean(),
            'member_of' => fake()->randomElement([
                TeamMemberOf::ALLIANCE,
                TeamMemberOf::HUB,
                TeamMemberOf::OTHER,
                TeamMemberOf::NCS,
            ]),
            'contact_point' => fake()->email(),
            'application_form_updated_by' => fake()->words(2, true),
            'application_form_updated_on' => fake()->dateTime(),
            'mongo_object_id' => null,
            'is_question_bank' => fake()->boolean(),
            'is_provider' => fake()->boolean(),
            'team_logo' => Config::get('services.media.base_url') . '/teams/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'url' => fake()->imageUrl(),
            'service' => 'https://service.local/test',
        ];
    }
}
