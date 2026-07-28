<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FederationJobRun>
 */
class FederationJobRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => 1,
            'federation_id' => 1,
            'pid' => 'mock-dataset-' . str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'job_uuid' => fake()->uuid(),
            'status' => 1,
            'details' => ['message' => 'CREATED'],
            'job_attempts' => 1,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 0,
            'details' => ['message' => fake()->randomElement([
                'Unable to validate dataset metadata against the expected schema.',
                'The remote dataset endpoint returned an unexpected response.',
                'Required field "publisher" was missing from the submitted metadata.',
                'Timed out while translating the dataset metadata.',
            ])],
        ]);
    }
}
