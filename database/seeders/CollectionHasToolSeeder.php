<?php

namespace Database\Seeders;

use App\Models\Tool;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Database\Seeder;
use App\Models\CollectionHasTool;

class CollectionHasToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $collectionId = Collection::all()->random()->id;
            $userId = User::all()->random()->id;
            $toolId = Tool::all()->random()->id;

            $collectionHasTool = CollectionHasTool::where([
                'collection_id' => $collectionId,
                'tool_id' => $toolId,
            ])->first();

            if (!$collectionHasTool) {
                CollectionHasTool::create([
                    'collection_id' => $collectionId,
                    'tool_id' => $toolId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
