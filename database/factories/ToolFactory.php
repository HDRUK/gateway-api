<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tool>
 */
class ToolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = Category::select('id')->get();

        return [
            'mongo_object_id' => fake()->regexify('[a-z0-9]{24}'),
            'name' => fake()->text(255),
            'url' => fake()->url(),
            'description' => fake()->text(255),
            'license' => fake()->text(45),
            'tech_stack' => fake()->text(45),
            'user_id' => User::all()->random()->id,
            'category_id' => fake()->randomElement($categories),
            'enabled' => fake()->randomElement([0, 1]),
            'type_category' => fake()->regexify('[A-Za-z0-9]{20}'), 
            'associated_authors' => fake()->regexify('[A-Za-z0-9]{20}'), 
            'contact_address' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}