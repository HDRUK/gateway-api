<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use App\Models\QuestionBankVersion;
use App\Models\DataAccessSection;


use Illuminate\Database\Seeder;

class QuestionBankSeeder extends Seeder
{
    /**d
     * Run the database seeds.
     */
    public function run(): void
    {


        DataAccessSection::truncate();
        QuestionBank::truncate();
        QuestionBankVersion::truncate();


        $path = storage_path() . '/migration_files/question_bank_data.json';
        $jsonString = file_get_contents($path);
        $jsonData = json_decode($jsonString, true);

        $count = 0;
        foreach ($jsonData as $section) {

            $sectionModel = DataAccessSection::create([
                'name' => $section['title'],
                'description' => $section['description'],
                'parent_section' => null,
                'order' => $count,
            ]);


            foreach ($section['subSections'] as $subSection) {
                $subSectionModel = DataAccessSection::create([
                    'name' => $subSection['name'],
                    'description' => $subSection['description'],
                    'parent_section' => $sectionModel->id,
                    'order' => $count,
                ]);


                foreach ($subSection['questions'] as $question) {
                    $questionModel = QuestionBank::create([
                            'section_id' => $subSectionModel->id,
                            'user_id' => 1,
                            'locked' => 0,
                            'archived' => 0,
                            'archived_date' => null,
                            'force_required' => $question['required'],
                            'allow_guidance_override' => 1,
                    ]);

                    QuestionBankVersion::create([
                        'question_id' => $questionModel->id,
                        'version' => 1,
                        'required' => $questionModel->force_required,
                        'default' => 0,
                        'question_json' => json_encode($question),
                        'deleted_at' => null,
                    ]);
                }
            }

            $count += 1;
        }

    }

}
