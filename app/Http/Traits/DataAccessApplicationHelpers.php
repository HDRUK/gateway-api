<?php

namespace App\Http\Traits;

use Config;
use Exception;
use Carbon\Carbon;
use App\Exceptions\UnauthorizedException;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationAnswer;
use App\Models\DataAccessApplicationReview;
use App\Models\DataAccessApplicationStatus;
use App\Models\Dataset;
use App\Models\QuestionBank;
use App\Models\Team;
use App\Models\TeamHasDataAccessApplication;
use Illuminate\Pagination\LengthAwarePaginator;

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

        $isDraft = in_array('DRAFT', array_column($application['teams']->toArray(), 'submission_status'));

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

        $isDraft = in_array('DRAFT', array_column($application['teams']->toArray(), 'submission_status'));

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

    public function dashboardIndex(
        array $applicationIds,
        ?string $filterTitle,
        ?string $filterApproval,
        ?string $filterSubmission,
        ?string $filterAction,
        ?int $teamId,
        ?int $userId,
    ): LengthAwarePaginator {
        $applications = DataAccessApplication::whereIn('id', $applicationIds)
            ->with('teams')
            ->when($filterTitle, function ($query) use ($filterTitle) {
                return $query->where('project_title', 'LIKE', "%{$filterTitle}%");
            })
            ->get();

        $matches = [];
        foreach ($applications as $a) {
            $matches[] = $a->id;
        }

        if (!is_null($filterApproval)) {
            $approvalMatches = [];
            foreach ($applications as $a) {
                foreach($a['teams'] as $t) {
                    if ((isset($teamId)) && ($t->team_id === $teamId) && ($t->approval_status === $filterApproval)) {
                        $approvalMatches[] = $a->id;
                    } elseif ((isset($userId)) && ($t->approval_status === $filterApproval)) {
                        $approvalMatches[] = $a->id;
                    }
                }
            }
            $matches = array_intersect($matches, $approvalMatches);
        }

        if (!is_null($filterSubmission)) {
            $submissionMatches = [];
            foreach ($applications as $a) {
                foreach($a['teams'] as $t) {
                    if ((isset($teamId)) && ($t->team_id === $teamId) && ($t->submission_status === $filterSubmission)) {
                        $submissionMatches[] = $a->id;
                    } elseif ((isset($userId)) && ($t->submission_status === $filterSubmission)) {
                        $submissionMatches[] = $a->id;
                    }
                }
            }
            $matches = array_intersect($matches, $submissionMatches);
        }

        if (!is_null($filterAction)) {
            $actionMatches = [];
            foreach ($matches as $m) {
                $reviews = DataAccessApplicationReview::where('application_id', $m)
                    ->select(['resolved'])->pluck('resolved')->toArray();
                $resolved = in_array(false, $reviews) ? false : true;

                if ((bool) $filterAction === $resolved) {
                    $actionMatches[] = $m;
                }
            }
            $matches = array_intersect($matches, $actionMatches);
        }

        $applications = DataAccessApplication::whereIn('id', $matches)
            ->with(['user:id,name,organisation','datasets','teams'])
            ->applySorting()
            ->paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

        foreach ($applications as $app) {
            foreach ($app['datasets'] as $d) {
                $dataset = Dataset::where('id', $d['dataset_id'])->first();
                $title = $dataset->getTitle();
                $custodian = Team::where('id', $dataset->team_id)->select(['id','name'])->first();
                $d['dataset_title'] = $title;
                $d['custodian'] = $custodian;
            }

            $submissionAudit = DataAccessApplicationStatus::where([
                'application_id' => $app->id,
                'submission_status' => 'SUBMITTED',
            ])->first();
            if ($submissionAudit) {
                $app['days_since_submission'] = $submissionAudit
                    ->updated_at
                    ->diffInDays(Carbon::today());
            } else {
                $app['days_since_submission'] = null;
            }
        }
        return $applications;
    }
}
