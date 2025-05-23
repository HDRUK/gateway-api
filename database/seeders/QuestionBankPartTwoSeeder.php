<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use App\Models\QuestionBankVersion;
use App\Models\QuestionBankVersionHasChildVersion;
use App\Models\DataAccessSection;
use Illuminate\Database\Seeder;

class QuestionBankPartTwoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = storage_path() . '/migration_files/question_bank_data_part_two.json';
        $jsonString = file_get_contents($path);
        $jsonData = json_decode($jsonString, true);

        $count = 0;
        foreach ($jsonData as $section) {
            $sectionModel = DataAccessSection::updateOrCreate(
                [
                'name' => $section['title'],
            ],
                [
                'name' => $section['title'],
                'description' => $section['description'],
                'parent_section' => null,
                'order' => $count,
            ]
            );

            foreach ($section['subSections'] as $subSection) {
                $subSectionModel = DataAccessSection::updateOrCreate(
                    [
                        'name' => $subSection['name']
                    ],
                    [
                    'name' => $subSection['name'],
                    'description' => $subSection['description'],
                    'parent_section' => $sectionModel->id,
                    'order' => $count,
                ]
                );


                foreach ($subSection['questions'] as $question) {
                    $questionModel = QuestionBank::create([
                            'section_id' => $subSectionModel->id,
                            'user_id' => 1,
                            'locked' => 0,
                            'archived' => 0,
                            'archived_date' => null,
                            'force_required' => $question['force_required'] ?? 0,
                            'allow_guidance_override' => 1,
                            'is_child' => 0,
                            'question_type' => QuestionBank::STANDARD_TYPE,
                        ]);

                    $questionForJson = [
                        'field' => [
                            'options' => array_column($question['field']['options'], 'value'),
                            'component' => $question['field']['component'],
                            'validations' => $question['field']['validations'],
                        ],
                        'title' => $question['title'],
                        'guidance' => $question['guidance'],
                    ];

                    $questionVersionModel = QuestionBankVersion::create([
                        'question_id' => $questionModel->id,
                        'version' => 1,
                        'required' => $question['required'],
                        'default' => 0,
                        'question_json' => $questionForJson,
                        'deleted_at' => null,
                    ]);

                    if (in_array($question['field']['component'], ['RadioGroup', 'CheckboxGroup'])) {
                        foreach ($question['field']['options'] as $option) {
                            if ($option['conditional']) {
                                foreach ($option['conditional'] as $subquestion) {
                                    $subquestionModel = QuestionBank::create([
                                        'section_id' => $subSectionModel->id,
                                        'user_id' => 1,
                                        'locked' => 0,
                                        'archived' => 0,
                                        'archived_date' => null,
                                        'force_required' => $question['force_required'] ?? 0,
                                        'allow_guidance_override' => 1,
                                        'is_child' => 1,
                                        'question_type' => QuestionBank::STANDARD_TYPE,
                                    ]);

                                    $component = $subquestion['input']['type'];

                                    $subquestionForJson = [
                                        'field' => [
                                            'options' => array_column($subquestion['field']['options'] ?? [], 'value'),
                                            'component' => $component,
                                            'validations' => $subquestion['validations'] ?? [],
                                        ],
                                        'title' => $subquestion['question'] ?? "",
                                        'guidance' => $subquestion['guidance'] ?? "",
                                    ];

                                    $subquestionVersionModel = QuestionBankVersion::create([
                                        'question_id' => $subquestionModel->id,
                                        'version' => 1,
                                        'required' => $subquestion['required'] ?? 0,
                                        'default' => 0,
                                        'question_json' => $subquestionForJson,
                                        'deleted_at' => null,
                                    ]);
                                    QuestionBankVersionHasChildVersion::create([
                                        'parent_qbv_id' => $questionVersionModel->id,
                                        'child_qbv_id' => $subquestionVersionModel->id,
                                        'condition' => $option['value'],
                                    ]);
                                }
                            }
                        }
                    } else {
                        // This should never trigger based on the contents of the migration file, but here for safety
                        foreach ($question['field']['options'] as $option) {
                            var_dump($question);
                            if ($option['conditional']) {
                                var_dump('warning, subquestion not created due to incorrect parent question type');
                            }
                        }
                    }
                }
            }

            $count += 1;
        }

    }

}
