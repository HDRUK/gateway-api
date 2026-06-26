<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Widget;
use Illuminate\Database\Seeder;

class WidgetSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::inRandomOrder()->limit(5)->get();

        foreach ($teams as $team) {
            Widget::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create(['team_id' => $team->id]);
        }
    }
}
