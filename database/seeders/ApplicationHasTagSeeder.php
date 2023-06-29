<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Application;
use Illuminate\Database\Seeder;
use App\Models\ApplicationHasTag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ApplicationHasTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appIds = Application::all()->pluck('id')->toArray();
        $tagIds = Tag::all()->pluck('id')->toArray();

        $count = 0;
        while ($count < 100) {
            $appId = $appIds[array_rand($appIds)];
            $tagId = $tagIds[array_rand($tagIds)];

            $appHasTag = ApplicationHasTag::where([
                'application_id' => $appId,
                'tag_id' => $tagId,
            ])->first();

            if (!$appHasTag) {
                ApplicationHasTag::create([
                    'application_id' => $appId,
                    'tag_id' => $tagId,
                ]);
                // $count += 1;
            }
            $count += 1;
        }
    }
}
