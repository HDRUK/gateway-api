<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use App\Models\QuestionBankVersion;
use App\Models\DataAccessSection;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    /**d
     * Run the database seeds.
     */
    public function run(): void
    {
        QuestionBank::truncate();
        QuestionBankVersion::truncate();
        
        //loop over each section
        DataAccessSection::all()->each(function ($section) {
            $nquestions = rand(2,10);
            
            //generate questions for this section
            QuestionBank::factory($nquestions)->create([
                'section_id' => $section->id,
            ]);

            //for each question, generate a version of the question
            QuestionBank::all()->each(function ($model) {

                $first = QuestionBankVersion::create([
                    'question_parent_id' => $model->id,
                    'version' => 1,
                    'required' => fake()->randomElement([0,1]),
                    'default' => 0,
                    'question_json' => $this->get_question_json(),
                    'deleted_at' => null,
                ]);

                //randomly create a 2nd version of the question
                if(mt_rand() / mt_getrandmax() < 0.2){ 
                    QuestionBankVersion::where("id",$first->id)->update([
                            'deleted_at' => fake()->dateTime(),
                        ]);
                    
                    QuestionBankVersion::create([
                        'question_parent_id' => $model->id,
                        'version' => 2,
                        'required' => $first->required,
                        'default' => 0,
                        'question_json' => $this->get_question_json()
                    ]);
                }

            });
        });
    }


    private function get_question_json(): string
    {
        $data = [
            "title" => fake()->sentence(4),
            "guidance" => fake()->paragraph(),
            "field" => $this->getField(),
        ];
        return json_encode($data);
    }
    private function getField(): array
    {
        $textArea = [
            "component"=> "TextArea",
            "variant"=> "outlined",
            "name"=> fake()->word(),
            "placeholder"=> fake()->sentence(3),
            "label"=> fake()->sentence(2),
            "showClearButton"=> true
        ];

        $select = [
                "component"=> "Select",
                "options"=> $this->getRandomOptions()
        ];
        return fake()->randomElement([$textArea,$select]);
    }

    private function getOption(): array
    {
        return [
            "label"=> fake()->word(),
            "value"=> fake()->word(),
        ];
    }
    private function getRandomOptions() {
        $n = rand(1,10);
        $options = [];
        for ($i = 0; $i < $n; $i++) {
            $options[] = $this->getOption();
        }
        return $options;
    }
}
