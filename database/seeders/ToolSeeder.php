<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

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
        
        // Seed additional tools that are not enabled
        Tool::factory()->count(2)->create([
            'enabled' => 0,
        ]);
    }
}
