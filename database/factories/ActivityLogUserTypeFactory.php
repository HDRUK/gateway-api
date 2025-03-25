<?php

namespace Database\Factories;

use App\Http\Enums\ActivityLogUserType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLogUserType>
 */
class ActivityLogUserTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                ActivityLogUserType::ADMIN,
                ActivityLogUserType::CUSTODIAN,
                ActivityLogUserType::APPLICANT,
            ]),
        ];
    }
}
