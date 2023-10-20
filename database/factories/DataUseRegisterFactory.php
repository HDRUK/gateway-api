<?php

namespace Database\Factories;

use App\Models\TeamHasUser;
use App\Models\Dataset;

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
        $datasetId = Dataset::all()->random()->id;

        $randomString = fake()->words(fake()->randomDigit(), true);

        $fakeROCrate = json_decode('{
            "@context": "https://w3id.org/ro/crate/1.2-DRAFT/context",
            "@graph": [
            ]
        }', true);

        return [
            'dataset_id' => $datasetId,
            'enabled' => fake()->boolean(),
            'user_id' => $teamHasUser->user_id,
            'ro_crate' => json_encode($fakeROCrate),
            'organization_name' => $randomString,
            'project_title' => $randomString,
            'lay_summary' => $randomString,
            'public_benefit_statement' => $randomString,
        ];
    }
}
