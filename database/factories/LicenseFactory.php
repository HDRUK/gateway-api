<?php

namespace Database\Factories;

use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition()
    {
        return [
            'code' => fake()->unique()->lexify('?????-?????'),
            'label' => fake()->word(),
            'valid_since' => fake()->dateTimeBetween('-10 years', 'now'),
            'valid_until' => fake()->dateTimeBetween('now', '+10 years'),
            'definition' => fake()->sentence(),
            'verified' => fake()->boolean(),
            'origin' => fake()->word(),
        ];
    }
}
