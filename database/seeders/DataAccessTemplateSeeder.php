<?php

namespace Database\Seeders;

use App\Models\DataAccessTemplate;
use App\Models\DataAccessTemplateHasQuestion;
use App\Models\DataAccessTemplateHasFile;
use App\Models\QuestionBank;
use Database\Seeders\Concerns\DisablesForeignKeyChecks;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataAccessTemplateSeeder extends Seeder
{
    use DisablesForeignKeyChecks;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->disableForeignKeyChecks();
        DataAccessTemplateHasFile::truncate();
        DataAccessTemplateHasQuestion::truncate();
        DataAccessTemplate::truncate();
        $this->enableForeignKeyChecks();

        DataAccessTemplate::factory(3)->create();

        DataAccessTemplate::all()->each(function ($model) {

            $nquestions = QuestionBank::count();
            $n = fake()->numberBetween(3, $nquestions);

            $numbers = range(1, $nquestions);
            shuffle($numbers);
            $count = 1;
            foreach (array_slice($numbers, 0, $n) as $i) {
                DataAccessTemplateHasQuestion::create([
                    'template_id' => $model->id,
                    'question_id' =>  $i,
                    'guidance' => fake()->paragraph(),
                    'required' =>  fake()->randomElement([0, 1]),
                    'order' => $count
                ]);
                $count++;
            }

        });

    }
}
