<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QuestionBankValidationObjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:question-bank-validation-objects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert question bank validations from arrays to objects.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::table('question_bank_versions')->chunkById(100, function ($questions) {
                foreach ($questions as $question) {
                    $questionJson = json_decode($question->question_json, true);
                    $validations = $questionJson['field']['validations'];
                    if (empty($validations)) {
                        $questionJson['field']['validations'] = null;
                    } else {
                        $validationObj = [];
                        foreach ($validations as $val) {
                            foreach ($val as $k => $v) {
                                $validationObj[$k] = $v;
                            }
                        }
                        $questionJson['field']['validations'] = $validationObj;
                    }
                    DB::table('question_bank_versions')
                        ->where('id', $question->id)
                        ->update(['question_json' => $questionJson]);
                }
            });

        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }
}
