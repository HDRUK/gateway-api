<?php

namespace Database\Factories;

use App\Models\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;

class WidgetFactory extends Factory
{
    protected $model = Widget::class;

    public function definition(): array
    {
        return [
            'team_id' => 1,
            'widget_name' => $this->faker->words(2, true),
            'size_width' => $this->faker->numberBetween(100, 600),
            'size_height' => $this->faker->numberBetween(100, 600),
            'unit' => 'px',
            'include_search_bar' => $this->faker->boolean(),
            'include_cohort_link' => $this->faker->boolean(),
            'keep_proportions' => $this->faker->boolean(),
            'permitted_domains' => $this->faker->domainName(),
        ];
    }
}
