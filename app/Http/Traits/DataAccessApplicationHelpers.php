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
use Illuminate\Database\Eloquent\Builder;

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

        $answers = $input['answers'] ?? null;
        if (!$answers) {
            return;
        }

        $isDraft = $application['submission_status'] === 'DRAFT';
        if (!$isDraft) {
            throw new Exception('DAR form answers cannot be updated after submission.');
        }

        DataAccessApplicationAnswer::where('application_id', $id)->delete();
        foreach ($answers as $answer) {
            DataAccessApplicationAnswer::create([
                'question_id' => $answer['question_id'],
                'application_id' => $id,
                'answer' => $answer['answer'],
                'contributor_id' => $input['applicant_id'],
            ]);
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

        $answers = $input['answers'] ?? null;
        if (!$answers) {
            return;
        }

        $isDraft = $application['submission_status'] === 'DRAFT';
        if (!$isDraft) {
            throw new Exception('DAR form answers cannot be edited after submission.');
        }

        DataAccessApplicationAnswer::where('application_id', $id)->delete();
        foreach ($answers as $answer) {
            DataAccessApplicationAnswer::create([
                'question_id' => $answer['question_id'],
                'application_id' => $id,
                'answer' => $answer['answer'],
                'contributor_id' => $application->applicant_id,
            ]);
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
                if (str_contains($a->approval_status, $filterApproval)) {
                    $approvalMatches[] = $a->id;
                }
            }
            $matches = array_intersect($matches, $approvalMatches);
        }

        if (!is_null($filterSubmission)) {
            $submissionMatches = [];
            foreach ($applications as $a) {
                if ($a->submission_status === $filterSubmission) {
                    $submissionMatches[] = $a->id;
                }
            }
            $matches = array_intersect($matches, $submissionMatches);
        }

        if (!is_null($filterAction)) {
            $actionMatches = [];
            foreach ($matches as $i => $m) {
                $review = DataAccessApplicationReview::where('application_id', $m)
                    ->latest()
                    ->with('comments')
                    ->first();
                if ($review) {
                    $latestComment = $review['comments'][array_key_last($review['comments']->toArray())];
                    $actionRequired = is_null($latestComment['team_id']) ? true : false;

                    if ((bool) $filterAction === $actionRequired) {
                        $actionMatches[] = $m;
                    }
                } elseif ((bool) $filterAction) {
                    $actionMatches[] = $m;
                }
            }
            $matches = array_intersect($matches, $actionMatches);
        }

        $applications = DataAccessApplication::whereIn('id', $matches)
            ->with(['user:id,name,organisation','datasets'])
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

            $app['days_since_submission'] = $this->submissionAudit($app->id)['days_since_submission'];
            $app['primary_applicant'] = $this->getPrimaryApplicantInfo($app->id);
        }
        return $applications;
    }

    public function submissionAudit(int $applicationId): array
    {
        $submissions = array(
            'days_since_submission' => null,
            'submission_date' => null,
        );
        $submissionAudit = DataAccessApplicationStatus::where([
            'application_id' => $applicationId,
            'submission_status' => 'SUBMITTED',
        ])->first();
        if ($submissionAudit) {
            $submissions['days_since_submission'] = $submissionAudit
                ->updated_at
                ->diffInDays(Carbon::today());
            $submissions['submission_date'] = $submissionAudit->updated_at;
            return $submissions;
        } else {
            return $submissions;
        }
    }

    public function statusCounts(Collection $applications): array
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
            if ($app['submission_status'] === 'DRAFT') {
                $counts['DRAFT'] += 1;
            } elseif (is_null($app['approval_status'])) {
                $counts['SUBMITTED'] += 1;
            } elseif (str_contains($app['approval_status'], 'APPROVED')) {
                $counts['APPROVED'] += 1;
            } else {
                $counts[$app['approval_status']] += 1;
            }
        }
        return $counts;
    }

    public function actionRequiredCounts(Collection $applications): array
    {
        $feedbackApplications = [];
        foreach ($applications as $app) {
            if ($app['approval_status'] === 'FEEDBACK') {
                $feedbackApplications[] = $app['id'];
            }
        }

        $actionRequired = 0;
        $infoRequired = 0;
        foreach ($feedbackApplications as $a) {
            $reviews = DataAccessApplicationReview::where('application_id', $a)
                ->with('comments')->get();

            if (!count($reviews)) {
                $actionRequired += 1;
                continue;
            }

            $reviewIds = array_column($reviews->toArray(), 'id');

            $review = DataAccessApplicationReview::whereIn('id', $reviewIds)
                ->latest()
                ->with('comments')
                ->first();
            $latestComment = $review['comments'][array_key_last($review['comments']->toArray())];
            $isActionRequired = is_null($latestComment['team_id']) ? true : false;

            if ($isActionRequired) {
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
        $applicantQuestions = QuestionBank::whereHas(
            'section',
            function (Builder $query) {
                $query->whereRaw(
                    'LOWER(name) LIKE ?',
                    ['%' . strtolower('Primary Applicant') . '%']
                );
            }
        )
        ->with('latestVersion')->get();

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
        $applicantName = $nameAnswer ? $nameAnswer->answer : null;

        $orgAnswer = DataAccessApplicationAnswer::where([
            'application_id' => $id,
            'question_id' => $applicantOrgQuestion,
        ])->first();
        $applicantOrg = $orgAnswer ? $orgAnswer->answer : null;

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

    public function groupApplicationsByProject(LengthAwarePaginator $applications): LengthAwarePaginator
    {
        $groups = $applications->groupBy('project_id');
        $applicationsResult = array();

        foreach ($groups as $projectId => $group) {
            $groupArray = array();
            $groupArray['project_id'] = $projectId;
            $teams = array();
            foreach ($group as $application) {
                $app = [
                    'approval_status' => $application['approval_status'],
                    'submission_status' => $application['submission_status'],
                    'project_title' => $application['project_title'],
                ];
                foreach ($application['teams'] as $t) {
                    $teams[] = array_merge($app, $t->toArray());
                }
            }
            $groupArray['teams'] = $teams;
            $applicationsResult[] = array_merge($group[0]->toArray(), $groupArray);
        }

        $page = $applications::resolveCurrentPage();
        $perPage = Config::get('constants.per_page');

        return new LengthAwarePaginator(
            $applicationsResult,
            count($applicationsResult),
            $perPage,
            $page
        );
    }

    public function returnApplicationsInProject(LengthAwarePaginator $applications): LengthAwarePaginator
    {
        $applicationsResult = array();

        foreach ($applications as $application) {
            $applicationArray = array();
            $sameProject = DataAccessApplication::where('project_id', $application['project_id'])
                ->whereNot('id', $application['id'])
                ->get();
            $teams = $application['teams'];
            foreach ($sameProject as $app) {
                $teams = array_merge($teams, $app['teams']->toArray());
            }
            $application['teams'] = $teams;
            $applicationsResult[] = $application;
        }

        $page = $applications::resolveCurrentPage();
        $perPage = Config::get('constants.per_page');

        return new LengthAwarePaginator(
            $applicationsResult,
            count($applicationsResult),
            $perPage,
            $page
        );
    }
}
