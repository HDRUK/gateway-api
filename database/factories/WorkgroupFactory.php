<?php

namespace Database\Factories;

use App\Models\Workgroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkgroupFactory extends Factory
{
    protected $model = Workgroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'active' => $this->faker->boolean(80),
        ];
    }
}
