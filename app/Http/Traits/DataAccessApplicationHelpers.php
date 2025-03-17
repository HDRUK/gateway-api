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
use App\Models\Role;
use App\Models\QuestionBank;
use App\Models\Team;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use App\Models\TeamHasDataAccessApplication;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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

            $app['primary_applicant'] = $this->getPrimaryApplicantInfo($app->id);
        }
        return $applications;
    }

    public function statusCounts(Collection $applications, ?int $teamId): array
    {
        $counts = array(
            'DRAFT' => 0,
            'SUBMITTED' => 0,
            'FEEDBACK' => 0,
            'APPROVED' => 0,
            'REJECTED' => 0,
            'WITHDRAWN' => 0,
        );
        foreach ($applications as $app) {
            foreach ($app['teams'] as $t) {
                if (is_null($teamId) || $t->team_id === $teamId) {
                    if ($t['submission_status'] === 'DRAFT') {
                        $counts['DRAFT'] += 1;
                    } elseif (is_null($t['approval_status'])) {
                        $counts['SUBMITTED'] += 1;
                    } elseif (str_contains($t['approval_status'], 'APPROVED')) {
                        $counts['APPROVED'] += 1;
                    } else {
                        $counts[$t['approval_status']] += 1;
                    }
                }
            }
        }
        return $counts;
    }

    public function actionRequiredCounts(Collection $applications, ?int $teamId): array
    {
        $feedbackApplications = [];
        foreach ($applications as $app) {
            $teams = $app['teams'];
            foreach ($teams as $team) {
                if (is_null($teamId) || ($team->team_id === $teamId)) {
                    if ($team['approval_status'] === 'FEEDBACK') {
                        $feedbackApplications[] = $app['id'];
                    }
                }
            }
        }

        $actionRequired = 0;
        $infoRequired = 0;
        foreach ($feedbackApplications as $a) {
            $reviews = DataAccessApplicationReview::where('application_id', $a)
                ->with('comments')->get();

            $reviewIds = [];
            if ($teamId) {
                foreach ($reviews as $r) {
                    foreach ($r['comments'] as $c) {
                        if ($c->team_id === $teamId) {
                            $reviewIds[] = $c->review_id;
                        }
                    }
                }
            } else {
                $reviewIds = array_column($reviews->toArray(), 'id');
            }

            $reviews = DataAccessApplicationReview::whereIn('id', $reviewIds)
                ->select(['resolved'])->pluck('resolved')->toArray();
            $resolved = in_array(false, $reviews) ? false : true;
            if ($resolved) {
                $actionRequired += 1;
            } else {
                $infoRequired += 1;
            }
        }
        return [
            'action_required' => $actionRequired,
            'info_required' => $infoRequired,
        ];
    }

    public function getPrimaryApplicantInfo(int $id): array
    {
        $applicantQuestions = QuestionBank::whereRelation(
            'section',
            'name',
            'Primary Applicant'
        )->with('latestVersion')->get();

        $applicantNameQuestion = null;
        $applicantOrgQuestion = null;
        foreach ($applicantQuestions as $q) {
            if ($q['latestVersion']['question_json']['title'] === 'Full name') {
                $applicantNameQuestion = $q->id;
            } elseif ($q['latestVersion']['question_json']['title'] === 'Your organisation name') {
                $applicantOrgQuestion = $q->id;
            }
        }

        $nameAnswer = DataAccessApplicationAnswer::where([
            'application_id' => $id,
            'question_id' => $applicantNameQuestion,
        ])->first();
        if ($nameAnswer) {
            $applicantName = $nameAnswer->answer['value'];
        } else {
            $applicantName = null;
        }

        $orgAnswer = DataAccessApplicationAnswer::where([
            'application_id' => $id,
            'question_id' => $applicantOrgQuestion,
        ])->first();
        if ($orgAnswer) {
            $applicantOrg = $orgAnswer->answer['value'];
        } else {
            $applicantOrg = null;
        }

        return [
            'name' => $applicantName,
            'organisation' => $applicantOrg,
        ];
    }

    public function getDarManagers(int $teamId): ?array
    {
        $team = Team::with('users')->where('id', $teamId)->first();
        $teamHasUserIds = TeamHasUser::where('team_id', $team->id)->get();
        $roleIdeal = null;
        $roleSecondary = null;

        $users = [];

        foreach ($teamHasUserIds as $thu) {
            $teamUserHasRoles = TeamUserHasRole::where('team_has_user_id', $thu->id)->get();

            foreach ($teamUserHasRoles as $tuhr) {
                $roleIdeal = Role::where([
                    'id' => $tuhr->role_id,
                    'name' => 'custodian.dar.manager',
                ])->first();

                $roleSecondary = Role::where([
                    'id' => $tuhr->role_id,
                    'name' => 'dar.manager',
                ])->first();

                if (!$roleIdeal && !$roleSecondary) {
                    continue;
                }

                $user = User::where('id', $thu['user_id'])->first()->toArray();

                $users[] = [
                    'to' => [
                        'email' => $user['email'],
                        'name' => $user['name'],
                    ],
                ];
            }
        }

        return $users;
    }
}
