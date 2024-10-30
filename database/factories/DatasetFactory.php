<?php

namespace Database\Factories;

use App\Models\Dataset;
use App\Models\TeamHasUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Filter>
 */
class DatasetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teamHasUser = TeamHasUser::all()->random();

        $origin = fake()->randomElement([
            Dataset::ORIGIN_MANUAL,
            Dataset::ORIGIN_API,
            Dataset::ORIGIN_GMI
        ]);

        return [
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => $origin,
            'status' => ($origin === Dataset::ORIGIN_MANUAL ? fake()->randomElement([
                Dataset::STATUS_ACTIVE,
                Dataset::STATUS_DRAFT,
                Dataset::STATUS_ARCHIVED,
            ]) : fake()->randomElement([
                Dataset::STATUS_ACTIVE,
                Dataset::STATUS_ARCHIVED,
            ])),
        ];
    }
}
