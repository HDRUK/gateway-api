<?php

namespace Database\Factories;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataAccessApplication>
 */
class DataAccessApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::all()->random();
        $submissionStatuses = [
            'DRAFT',
            'SUBMITTED',
            'FEEDBACK',
        ];

        $approvalStatuses = [
            'APPROVED',
            'APPROVED_COMMENTS',
            'REJECTED',
        ];

        return [
            'applicant_id' => $user->id,
            'submission_status' => fake()->randomElement($submissionStatuses),
            'approval_status' => fake()->randomElement($approvalStatuses),
            'project_title' => fake()->text(255),
        ];
    }
}
