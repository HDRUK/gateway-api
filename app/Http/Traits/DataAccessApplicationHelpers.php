<?php

namespace App\Http\Traits;

use Exception;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationAnswer;

trait DataAccessApplicationHelpers
{
    use RequestTransformation;

    public function updateDataAccessApplication(DataAccessApplication $application, array $input): void
    {
        $id = $application->id;

        $application->update([
            'applicant_id' => $input['applicant_id'],
            'submission_status' => $input['submission_status'],
            'approval_status' => isset($input['approval_status']) ? $input['approval_status'] : $application->approval_status,
            'project_title' => $input['project_title'],
        ]);

        $answers = $input['answers'] ?? [];
        if (count($answers)) {
            if ($application->submission_status !== 'SUBMITTED') {
                DataAccessApplicationAnswer::where('application_id', $id)->delete();
                foreach ($answers as $answer) {
                    DataAccessApplicationAnswer::create([
                        'question_id' => $answer['question_id'],
                        'application_id' => $id,
                        'answer' => $answer['answer'],
                        'contributor_id' => $input['applicant_id'],
                    ]);
                }
            } else {
                throw new Exception('DAR form answers cannot be updated after submission.');
            }
        }
    }

    public function editDataAccessApplication(DataAccessApplication $application, array $input): void
    {
        $id = $application->id;

        $arrayKeys = [
            'applicant_id',
            'submission_status',
            'approval_status',
            'project_title',
        ];
        $array = $this->checkEditArray($input, $arrayKeys);
        $application->update($array);

        $answers = $input['answers'] ?? [];
        if (count($answers)) {
            if ($application->submission_status !== 'SUBMITTED') {
                DataAccessApplicationAnswer::where('application_id', $id)->delete();
                foreach ($answers as $answer) {
                    DataAccessApplicationAnswer::create([
                        'question_id' => $answer['question_id'],
                        'application_id' => $id,
                        'answer' => $answer['answer'],
                        'contributor_id' => $application->applicant_id,
                    ]);
                }
            } else {
                throw new Exception('DAR form answers cannot be updated after submission.');
            }
        }
    }
}
