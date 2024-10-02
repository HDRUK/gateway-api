<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $sectors = Sector::select('id')->get();
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => $firstName . ' ' . $lastName,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'secondary_email' => fake()->unique()->safeEmail(),
            'preferred_email' => fake()->randomElement(['primary', 'secondary']),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'sector_id' => fake()->randomElement($sectors),
            'organisation' => fake()->words(3, true),
            'bio' => fake()->sentence(),
            'domain' => fake()->domainName(),
            'link' => fake()->url(),
            'orcid' => sprintf('https://orcid.org/%d', fake()->randomNumber(8, true)),
            'contact_feedback' => fake()->numberBetween(0, 1),
            'contact_news' => fake()->numberBetween(0, 1),
            'mongo_id' => fake()->randomNumber(8, true),
            'mongo_object_id' => fake()->words(1, true),
            'is_admin' => false,
            'terms' => fake()->numberBetween(0, 1),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
