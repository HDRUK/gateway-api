<?php

namespace Database\Factories;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Library>
 */
class LibraryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::all()->random();
        $dataset = Dataset::all()->random();

        return [
            'user_id' => $user['id'],
            'dataset_id' => $dataset['id'],
        ];
    }
}
