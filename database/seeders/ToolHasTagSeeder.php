<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Tool;
use App\Models\ToolHasTag;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ToolHasTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 100; $count++) {
            $toolId = Tool::all()->random()->id;
            $tagId = Tag::all()->random()->id;

            $toolHasTags = ToolHasTag::where([
                'tool_id' => $toolId,
                'tag_id' => $tagId,
            ])->first();

            if (!$toolHasTags) {
                ToolHasTag::create([
                    'tool_id' => $toolId,
                    'tag_id' => $tagId,
                ]);
            }
        }
    }
}
