<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\TypeCategory;
use Illuminate\Database\Seeder;
use App\Models\ToolHasTypeCategory;

class ToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed 8 enabled tools
        Tool::factory()->count(8)->create([
            'enabled' => 1,
        ]);

        // Seed additional tools
        Tool::factory()->create(
            [
                'team_id' => Team::all()->random()->id,
                'user_id' => User::all()->random()->id,
                'status' => Tool::STATUS_ACTIVE,
                'enabled' => 1,
            ]
        );

        Tool::factory(1)->count(2)->create([
            'enabled' => 0,
        ]);

        $tools = Tool::all();
        foreach ($tools as $tool) {
            $typeCategoryId = TypeCategory::all()->random()->id;

            ToolHasTypeCategory::create([
                'tool_id' => $tool->id,
                'type_category_id' => $typeCategoryId,
            ]);
        }
    }
}
