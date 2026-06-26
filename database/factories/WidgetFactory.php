<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Widget>
 */
class WidgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id'            => Team::factory(),
            'widget_name'        => fake()->words(3, true),
            'size_width'         => fake()->numberBetween(300, 1200),
            'size_height'        => fake()->numberBetween(200, 800),
            'unit'               => fake()->randomElement(['px', '%', 'rem']),
            'include_search_bar' => fake()->boolean(),
            'include_cohort_link' => fake()->boolean(),
            'keep_proportions'   => fake()->boolean(),
            'permitted_domains'  => implode(',', [
                fake()->domainName(),
                fake()->domainName(),
            ]),
            'branding_primary'   => fake()->hexColor(),
            'branding_secondary' => fake()->hexColor(),
            'branding_neutral'   => fake()->hexColor(),
        ];
    }
}
