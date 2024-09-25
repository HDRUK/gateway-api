<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataProviderColl>
 */
class DataProviderCollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enabled' => 1,
            'name' => fake()->word(),
            'summary' => fake()->text(255),
            'img_url' => 'https://fakeimg.pl/300x200',
            'service' => 'https://service.local/test',
        ];
    }
}
