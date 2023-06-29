<?php

namespace Database\Seeders;

use App\Models\AppHasTag;
use App\Models\AppRegistration;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppHasTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appIds = AppRegistration::all()->pluck('id')->toArray();
        $tagIds = Tag::all()->pluck('id')->toArray();

        $count = 0;
        while ($count < 100) {
            $appId = $appIds[array_rand($appIds)];
            $tagId = $tagIds[array_rand($tagIds)];

            $appHasTag = AppHasTag::where([
                'app_id' => $appId,
                'tag_id' => $tagId,
            ])->first();

            if (!$appHasTag) {
                AppHasTag::create([
                    'app_id' => $appId,
                    'tag_id' => $tagId,
                ]);
                // $count += 1;
            }
            $count += 1;
        }
    }
}
