<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use App\Models\QuestionBankVersion;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        QuestionBank::factory(10)->create();
        QuestionBank::all()->each(function ($model) {
            QuestionBankVersion::create([
                'question_parent_id' => $model->id,
                'version' => 1,
                'required' => fake()->randomElement([0,1]),
                'default' => 0,
                'question_json' => '{}'
            ]);
        });
    }
}
