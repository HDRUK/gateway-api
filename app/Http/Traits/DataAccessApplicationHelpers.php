<?php

namespace App\Http\Traits;

use Exception;
use App\Exceptions\UnauthorizedException;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationAnswer;
use App\Models\QuestionBank;

trait DataAccessApplicationHelpers
{
    use QuestionBankHelpers;
    use RequestTransformation;

    public function getApplicationWithQuestions(DataAccessApplication $application): void
    {
        foreach ($application['questions'] as $i => $q) {
            $applicationSpecificFields = [
                'application_id' => $q['application_id'],
                'question_id' => $q['question_id'],
                'guidance' => $q['guidance'],
                'required' => $q['required'],
                'order' => $q['order'],
                'template_teams' => $q['teams'],
            ];
            $version = QuestionBank::with([
                'latestVersion',
                'latestVersion.childVersions',
                'teams',
            ])->where('id', $q->question_id)->first();
            if ($version) {
                $vArr = $version->toArray();
                $question = $this->getVersion($vArr);
                $application['questions'][$i] = array_merge(
                    $question,
                    $applicationSpecificFields
                );
            }
        }
    }

    public function updateDataAccessApplication(DataAccessApplication $application, array $input): void
    {
        $id = $application->id;

        $application->update([
            'applicant_id' => $input['applicant_id'],
            'project_title' => $input['project_title'],
        ]);

        $isDraft = in_array('DRAFT', array_column($application['teams'], 'submission_status'));

        $answers = $input['answers'] ?? [];
        if (count($answers)) {
            if ($isDraft) {
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
            'project_title',
        ];
        $array = $this->checkEditArray($input, $arrayKeys);
        $application->update($array);

        $isDraft = in_array('DRAFT', array_column($application['teams'], 'submission_status'));

        $answers = $input['answers'] ?? [];
        if (count($answers)) {
            if ($isDraft) {
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

    public function checkTeamAccess(int $teamId, int $id, string $op): void
    {
        $access = count(
            TeamHasDataAccessApplication::where([
                'team_id' => $teamId,
                'dar_application_id' => $id
            ])->get()
        );

        if (!$access) {
            throw new UnauthorizedException(
                "Team does not have permission to use this endpoint to $op this application."
            );
        }
    }
}
