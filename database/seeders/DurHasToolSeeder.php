<?php

namespace Database\Seeders;

use App\Models\Dur;
use App\Models\DurHasTool;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class DurHasToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 10; $count++) {
            $durId = Dur::all()->random()->id;
            $toolId = Tool::all()->random()->id;

            $durHasTool = DurHasTool::where([
                'dur_id' => $durId,
                'tool_id' => $toolId,
            ])->first();

            if (!$durHasTool) {
                DurHasTool::create([
                    'dur_id' => $durId,
                    'tool_id' => $toolId,
                ]);
            }
        }
    }
}
