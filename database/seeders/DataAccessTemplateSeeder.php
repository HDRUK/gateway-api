<?php

namespace Database\Seeders;

use App\Models\DataAccessTemplate;
use App\Models\DataAccessTemplateHasQuestion;
use App\Models\QuestionBank;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DataAccessTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DataAccessTemplate::factory(3)->create();

        DataAccessTemplate::all()->each(function ($model) {

            DataAccessTemplateHasQuestion::create([
                'template_id' => $model->id,
                'question_id' =>  QuestionBank::all()->random()->id,
                'guidance' => fake()->paragraph(),
                'required' =>  fake()->randomElement([0, 1]),
                'order' => fake()->randomNumber(10,true),
            ]);
          
        });

    }
}
