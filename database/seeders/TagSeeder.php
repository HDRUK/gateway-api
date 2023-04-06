<?php

namespace Database\Seeders;

use App\Http\Enums\TagType;
use App\Models\Tag;
use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'type' => TagType::TOPICS,
                'description' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => TagType::FEATURES,
                'description' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        Tag::insert($data);
    }
}
