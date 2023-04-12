<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DarIntegration>
 */
class DarIntegrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enabled' => fake()->randomElement([0, 1]),
            'notification_email' => fake()->unique()->safeEmail(),
            'outbound_auth_type' => 'auth_type',
            'outbound_auth_key' => 'auth_key',
            'outbound_endpoints_base_url' => fake()->domainName(),
            'outbound_endpoints_enquiry' => fake()->slug(),
            'outbound_endpoints_5safes' => fake()->slug(),
            'outbound_endpoints_5safes_files' => fake()->slug(),
            'inbound_service_account_id' => Str::random(fake()->randomElement([50, 101, 255])),
        ];
    }
}
