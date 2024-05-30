<?php

namespace Database\Seeders;

use App\Models\Tool;
use App\Models\User;
use App\Models\Publication;
use App\Models\PublicationHasTool;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PublicationHasToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 50; $count++) {
            $publicationId = Publication::all()->random()->id;
            $userId = User::all()->random()->id;
            $toolId = Tool::all()->random()->id;

            $publicationHasTool = PublicationHasTool::where([
                'publication_id' => $publicationId,
                'tool_id' => $toolId,
            ])->first();

            if (!$publicationHasTool) {
                PublicationHasTool::create([
                    'publication_id' => $publicationId,
                    'tool_id' => $toolId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }  
    }
}
