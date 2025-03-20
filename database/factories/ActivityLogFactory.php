<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ActivityLogType;
use App\Models\ActivityLogUserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    use WithFaker;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $users = User::all();
        $activityLogTypes = ActivityLogType::all();
        $activityLogUserTypes = ActivityLogUserType::all();

        $randomString = fake()->words(fake()->randomDigit(), true);

        return [
            'event_type' => 'demo_data',
            'user_type_id' => fake()->randomElement($activityLogUserTypes)->id,
            'log_type_id' => fake()->randomElement($activityLogTypes)->id,
            'user_id' => User::all()->random()->id,
            'version' => '2.1.0',
            'html' => sprintf('<b>%s</b>', $randomString),
            'plain_text' => $randomString,
            'user_id_mongo' => '00000000000000000000',
            'version_id_mongo' => '00000000000000000000',
        ];
    }
}
