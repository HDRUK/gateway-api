<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataAccessApplication\CreateUserDataAccessApplicationAnswer;
use App\Http\Requests\DataAccessApplication\EditUserDataAccessApplication;
use App\Http\Requests\DataAccessApplication\GetUserDataAccessApplication;
use App\Http\Requests\DataAccessApplication\UpdateUserDataAccessApplication;
use App\Http\Traits\DataAccessApplicationHelpers;
use App\Jobs\SendEmailJob;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationAnswer;
use App\Models\DataAccessApplicationHasDataset;
use App\Models\Dataset;
use App\Models\EmailTemplate;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use App\Models\User;

class UserDataAccessApplicationController extends Controller
{
    use DataAccessApplicationHelpers;

    /**
     * @OA\Get(
     *      path="/api/v1/users/{userId}/dar/applications/{id}",
     *      summary="Return a DAR application belonging to the user",
     *      description="Return a DAR application belonging to the user",
     *      tags={"UserDataAccessApplication"},
     *      summary="UserDataAccessApplication@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="User id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="applicant_id", type="integer", example="1"),
     *                  @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                  @OA\Property(property="project_title", type="string", example="A DAR project"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *                  @OA\Property(property="questions", type="array", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function show(GetUserDataAccessApplication $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::where('id', $id)->with('questions')->first();

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to view this application.');
            }

            $this->getApplicationWithQuestions($application);

            if ($application) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataAccessApplication get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $application,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/users/{userId}/dar/applications/{id}/answers",
     *      summary="Return answers from the user's DAR application",
     *      description="Return answers from the user's DAR application",
     *      tags={"UserDataAccessApplication"},
     *      summary="UserDataAccessApplication@showAnswers",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="User id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="answers", type="array", @OA\Items()),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     */
    public function showAnswers(GetUserDataAccessApplication $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::where('id', $id)->first();

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to view this application.');
            }

            $answers = DataAccessApplicationAnswer::where('application_id', $id)->get();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication answers get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $answers,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/users/{userId}/dar/applications/{id}",
     *      summary="Update a system DAR application",
     *      description="Update a system DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplication definition",
     *          @OA\JsonContent(
     *              required={"applicant_id","submission_status","approval_status"},
     *              @OA\Property(property="applicant_id", type="integer", example="1"),
     *              @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *              @OA\Property(property="project_title", type="string", example="A DAR project"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *              @OA\Property(property="team_ids", type="array", @OA\Items()),
     *              @OA\Property(property="answers", type="array", @OA\Items(
     *                  @OA\Property(property="question_id", type="integer", example="123"),
     *                  @OA\Property(property="answer", type="object",
     *                      @OA\Property(property="value", type="string", example="an answer"),
     *                  ),
     *              ))
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="applicant_id", type="integer", example="1"),
     *                  @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function update(UpdateUserDataAccessApplication $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to update this application.');
            }

            $originalStatus = $application->submission_status;
            $newStatus = $input['submission_status'] ?? null;

            if (($newStatus === 'SUBMITTED') && ($originalStatus != 'SUBMITTED')) {
                $this->emailSubmissionNotification($id, $userId, $application);
            }

            $this->updateDataAccessApplication($application, $input);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplication::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/users/{userId}/dar/applications/{id}/answers",
     *      summary="Add answers to the user's DAR application",
     *      description="Add answers to the user's DAR application",
     *      tags={"UserDataAccessApplication"},
     *      summary="UserDataAccessApplication@storeAnswers",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="User id",
     *         ),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="UserDataAccessApplication definition",
     *          @OA\JsonContent(
     *              required={},
     *              @OA\Property(property="answers", type="array", @OA\Items(
     *                  @OA\Property(property="question_id", type="integer", example="123"),
     *                  @OA\Property(property="answer", type="object",
     *                      @OA\Property(property="value", type="string", example="an answer"),
     *                  ),
     *              ))
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example="100")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function storeAnswers(CreateUserDataAccessApplicationAnswer $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to update this application.');
            }

            if ($application->submission_status !== 'SUBMITTED') {
                foreach ($input['answers'] as $answer) {
                    DataAccessApplicationAnswer::where([
                        'question_id' => $answer['question_id'],
                        'application_id' => $id,
                    ])->delete();
                    DataAccessApplicationAnswer::create([
                        'question_id' => $answer['question_id'],
                        'application_id' => $id,
                        'answer' => $answer['answer'],
                        'contributor_id' => $jwtUser['id'],
                    ]);
                }
            } else {
                throw new Exception('DAR form answers cannot be updated after submission.');
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' answer created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/users/{userId}/dar/applications/{id}",
     *      summary="Edit a system DAR application",
     *      description="Edit a system DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR application id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR application id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplication definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="applicant_id", type="integer", example="1"),
     *              @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *              @OA\Property(property="project_title", type="string", example="A DAR project"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *              @OA\Property(property="team_ids", type="array", @OA\Items()),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="applicant_id", type="integer", example="1"),
     *                  @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                  @OA\Property(property="project_title", type="string", example="A DAR project"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error")
     *          )
     *      )
     * )
     */
    public function edit(EditUserDataAccessApplication $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to edit this application.');
            }

            $originalStatus = $application->submission_status;
            $newStatus = $input['submission_status'] ?? null;

            if (($newStatus === 'SUBMITTED') && ($originalStatus != 'SUBMITTED')) {
                $this->emailSubmissionNotification($id, $userId, $application);
            }

            $this->editDataAccessApplication($application, $input);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplication::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function emailSubmissionNotification(int $id, int $userId, DataAccessApplication $application): void
    {
        $template = EmailTemplate::where(['identifier' => 'dar.submission.researcher'])->first();
        $user = User::where('id', '=', $userId)->first();

        $datasets = DataAccessApplicationHasDataset::where('dar_application_id', $id)
            ->select('dataset_id')
            ->pluck('dataset_id');
        $teams = [];
        foreach ($datasets as $d) {
            $metadata = Dataset::findOrFail($d)->lastMetadata();
            $gatewayId = $metadata['metadata']['summary']['publisher']['gatewayId'];
            $team = Team::where('id', $gatewayId)->first();
            if (!$team) {
                $team = Team::where('pid', $gatewayId)->first();
                if (!$team) {
                    continue;
                }
            }
            $teams[] = $team;
        }

        $to = [
            'to' => [
                'email' => $user['email'],
                'name' => $user['name'],
            ],
        ];

        $teamNames = array_column($teams, 'name');
        $custodiansList = $this->formatTeamNames($teamNames);

        $replacements = [
            '[[USER_FIRST_NAME]]' => $user['firstname'],
            '[[PROJECT_TITLE]]' => $application->project_title,
            '[[CUSTODIANS]]' => $custodiansList,
            '[[APPLICATION_ID]]' => $id,
            '[[CURRENT_YEAR]]' => date("Y"),
        ];

        SendEmailJob::dispatch($to, $template, $replacements);

        $custodianTemplate = EmailTemplate::where(['identifier' => 'dar.submission.custodian'])->first();
        foreach ($teams as $team) {
            $darManagers = $this->getDarManagers($team->id);
            foreach ($darManagers as $dm) {
                $replacements = [
                    '[[USER_FIRST_NAME]]' => $user['firstname'],
                    '[[RESEARCHER_NAME]]' => $user['name'],
                    '[[DATE_OF_APPLICATION]]' => date('d-m-Y'),
                    '[[RECIPIENT_NAME]]' => $dm['to']['name'],
                    '[[CUSTODIANS]]' => $custodiansList,
                    '[[CURRENT_YEAR]]' => date('Y'),
                    '[[TEAM_ID]]' => $team->id,
                ];
                SendEmailJob::dispatch($dm, $custodianTemplate, $replacements);
            }
        }
    }

    private function formatTeamNames(array $teamNames): string
    {
        $formatted = "";
        if (count($teamNames)) {
            $formatted = '<ul>';
            foreach ($teamNames as $name) {
                $formatted .= '<li>' . $name . '</li>';
            }
            $formatted .= '</ul>';
        }

        return $formatted;
    }

    private function getDarManagers(int $teamId): ?array
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
