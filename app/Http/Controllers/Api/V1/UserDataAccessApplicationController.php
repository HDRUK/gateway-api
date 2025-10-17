<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataAccessApplication\CreateUserDataAccessApplicationAnswer;
use App\Http\Requests\DataAccessApplication\EditUserDataAccessApplication;
use App\Http\Requests\DataAccessApplication\DeleteUserDataAccessApplication;
use App\Http\Requests\DataAccessApplication\DeleteUserDataAccessApplicationFile;
use App\Http\Requests\DataAccessApplication\GetUserDataAccessApplication;
use App\Http\Requests\DataAccessApplication\GetUserDataAccessApplicationFile;
use App\Http\Requests\DataAccessApplication\UpdateUserDataAccessApplication;
use App\Http\Traits\DataAccessApplicationHelpers;
use App\Jobs\SendEmailJob;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationAnswer;
use App\Models\DataAccessApplicationHasDataset;
use App\Models\DataAccessApplicationHasQuestion;
use App\Models\DataAccessApplicationStatus;
use App\Models\DataAccessTemplate;
use App\Models\EmailTemplate;
use App\Models\Team;
use App\Models\TeamHasDataAccessApplication;
use App\Models\Upload;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserDataAccessApplicationController extends Controller
{
    use DataAccessApplicationHelpers;

    /**
     * @OA\Get(
     *      path="/api/v1/users/{userId}/dar/applications",
     *      summary="List of dar applications belonging to a user",
     *      description="List of dar applications belonging to a user",
     *      tags={"UserDataAccessApplication"},
     *      summary="UserDataAccessApplication@index",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="applicant_id", type="integer", example="1"),
     *                      @OA\Property(property="project_title", type="string", example="A project"),
     *                      @OA\Property(property="user", type="array", @OA\Items(
     *                          @OA\Property(property="name", type="string", example="A User"),
     *                          @OA\Property(property="organisation", type="string", example="An origanisation"),
     *                      )),
     *                      @OA\Property(property="datasets", type="array", @OA\Items(
     *                          @OA\Property(property="dar_application_id", type="integer", example="1"),
     *                          @OA\Property(property="dataset_id", type="integer", example="1"),
     *                          @OA\Property(property="dataset_title", type="string", example="A dataset"),
     *                          @OA\Property(property="custodian", type="array", @OA\Items(
     *                              @OA\Property(property="name", type="string", example="A Custodian"),
     *                          )),
     *                      )),
     *                      @OA\Property(property="teams", type="array", @OA\Items(
     *                          @OA\Property(property="team_id", type="integer", example="1"),
     *                          @OA\Property(property="dar_application_id", type="integer", example="1"),
     *                          @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                          @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *                      )),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            if ($jwtUser['id'] != $userId) {
                throw new UnauthorizedException('Logged in user does match user id in endpoint.');
            }

            $applicationIds = DataAccessApplication::where('applicant_id', $userId)
                ->select('id')
                ->pluck('id');

            $filterTitle = $request->query('project_title', null);
            $filterApproval = $request->query('approval_status', null);
            $filterSubmission = $request->query('submission_status', null);
            $filterAction = isset($input['action_required']) ?
                $request->boolean('action_required', null) : null;

            $applications = $this->dashboardIndex(
                $applicationIds->toArray(),
                $filterTitle,
                $filterApproval,
                $filterSubmission,
                $filterAction,
                null,
                $userId,
            );

            $projectGroups = $request->boolean('project_groups', true);
            if ($projectGroups) {
                $applications = $this->groupApplicationsByProject($applications);
            }
            $applications = $this->returnApplicationsInProject($applications);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication get all by user',
            ]);

            return response()->json(
                $applications
            );
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
     *    path="/api/v1/users/{userId}/dar/applications/count/{field}",
     *    tags={"UserDataAccessApplications"},
     *    summary="UserDataAccessApplicationController@count",
     *    description="Get Counts for distinct entries of a field in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="field",
     *       in="path",
     *       description="name of the field to perform a count on",
     *       required=true,
     *       example="approval_status",
     *       @OA\Schema(
     *          type="string",
     *          description="approval status field",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *          )
     *       )
     *    )
     * )
     */
    public function count(Request $request, int $userId, string $field): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            if ($jwtUser['id'] != $userId) {
                throw new UnauthorizedException('Logged in user does match user id in endpoint.');
            }

            $applications = DataAccessApplication::where('applicant_id', $userId)
                ->get();

            if ($field === 'action_required') {
                $counts = $this->actionRequiredCounts($applications);
            } else {
                $counts = array();
                foreach ($applications as $app) {
                    if (array_key_exists($app[$field], $counts)) {
                        $counts[$app[$field]] += 1;
                    } else {
                        $counts[$app[$field]] = 1;
                    }
                }
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'User DAR application count',
            ]);

            return response()->json([
                'data' => $counts
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/users/{userId}/dar/applications/count",
     *    tags={"UserDataAccessApplications"},
     *    summary="UserDataAccessApplicationController@allCounts",
     *    description="Get Counts for all status fields in the model",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="object",
     *          )
     *       )
     *    )
     * )
     */
    public function allCounts(Request $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            if ($jwtUser['id'] != $userId) {
                throw new UnauthorizedException('Logged in user does match user id in endpoint.');
            }

            $applications = DataAccessApplication::where('applicant_id', $userId)
                ->get();

            $counts = $this->statusCounts($applications);

            $actionCounts = $this->actionRequiredCounts($applications);
            $counts = array_merge($counts, $actionCounts);
            $counts['ALL'] = count(TeamHasDataAccessApplication::whereIn(
                'dar_application_id',
                array_column($applications->toArray(), 'id')
            )->get());

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "User DAR application count",
            ]);

            return response()->json([
                'data' => $counts
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

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
     *                  @OA\Property(property="project_title", type="string", example="A DAR project"),
     *                  @OA\Property(property="questions", type="array", @OA\Items()),
     *                  @OA\Property(property="teams", type="array", @OA\Items(
     *                      @OA\Property(property="team_id", type="integer", example="1"),
     *                      @OA\Property(property="dar_application_id", type="integer", example="1"),
     *                      @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                      @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *                  )),
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
            $application = DataAccessApplication::where('id', $id)->with(['questions'])->first();

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to view this application.');
            }

            $groupArrays = $request->boolean('group_arrays', false);

            if ($application->application_type === 'FORM') {
                $this->getApplicationWithQuestions($application);
            } else {
                $teams = TeamHasDataAccessApplication::where('dar_application_id', $id)
                    ->select('team_id')
                    ->pluck('team_id');
                $templates = DataAccessTemplate::whereIn('team_id', $teams)
                    ->where('template_type', 'DOCUMENT')
                    ->select('id')
                    ->get();
                $application['templates'] = $templates;
            }

            $application = $application->toArray();

            if ($groupArrays) {
                $questionsGrouped = $this->groupArraySections($application);
                $application = array_merge($application, ['questions' => $questionsGrouped]);
            }

            $submissions = $this->submissionAudit($id);
            $application = array_merge($application, $submissions);

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
     * @OA\Get(
     *      path="/api/v1/users/{userId}/dar/applications/{id}/files",
     *      summary="Return a list of files associated with a DAR application",
     *      description="Return a list of files associated with a DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@showFiles",
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
     *                  @OA\Property(property="filename", type="string"),
     *                  @OA\Property(property="file_location", type="string"),
     *                  @OA\Property(property="user_id", type="string"),
     *                  @OA\Property(property="status", type="string"),
     *                  @OA\Property(property="application_id", type="integer"),
     *                  @OA\Property(property="question_id", type="integer"),
     *                  @OA\Property(property="error", type="string")
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
    public function showFiles(GetUserDataAccessApplication $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);
            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to view these files.');
            }
            $uploads = Upload::where('entity_id', $id)->get();

            if ($uploads) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataAccessApplication list files ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $uploads,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
     *      path="/api/v1/users/{userId}/dar/applications/{id}/files/{fileId}/download",
     *      summary="Download a file associated with a DAR application",
     *      description="Download a file associated with a DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@downloadFile",
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
     *         name="fileId",
     *         in="path",
     *         description="File id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="File id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="file"
     *          )
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
    public function downloadFile(GetUserDataAccessApplicationFile $request, int $userId, int $id, int $fileId): StreamedResponse | JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);
            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to download this files.');
            }
            $file = Upload::where('id', $fileId)->first();

            if ($file) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataAccessApplication ' . $id . ' download file ' . $fileId,
                ]);

                return Storage::disk(env('SCANNING_FILESYSTEM_DISK', 'local_scan') . '_scanned')
                    ->download($file->file_location);
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
            $application = DataAccessApplication::where('id', $id)->first();

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to update this application.');
            }

            $originalStatus = $application['submission_status'];
            $newStatus = $input['submission_status'] ?? null;

            $this->updateDataAccessApplication($application, $input);

            if (($newStatus === 'SUBMITTED') && ($originalStatus != 'SUBMITTED')) {
                $application->update([
                    'submission_status' => $newStatus,
                    'is_joint' => false,
                ]);
                $this->splitSubmittedApplication($application);
                $this->emailSubmissionNotification($id, $userId, $application);
            }

            if (isset($input['approval_status'])) {
                $application->update(['approval_status' => $input['approval_status']]);
            }

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
            $application = DataAccessApplication::where('id', $id)->first();

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to update this application.');
            }

            $status = $application['submission_status'];

            if ($status !== 'SUBMITTED') {
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
            $application = DataAccessApplication::where('id', $id)->first();

            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to edit this application.');
            }

            $status = $application['submission_status'];
            $newStatus = $input['submission_status'] ?? null;
            $preApproval = is_null($application['approval_status']);

            $this->editDataAccessApplication($application, $input);

            if (!is_null($newStatus)) {
                $thd = TeamHasDataAccessApplication::where([
                    'dar_application_id' => $id
                ])->get();

                if (($newStatus === 'SUBMITTED') && ($status !== 'SUBMITTED')) {
                    $application->update([
                        'submission_status' => $newStatus,
                        'is_joint' => false,
                    ]);
                    $this->splitSubmittedApplication($application);
                    $this->emailSubmissionNotification($id, $userId, $application);
                } elseif (($newStatus === 'DRAFT') && $preApproval) {
                    $application->update(['submission_status' => $newStatus]);
                } else {
                    throw new Exception('The status of this data access request cannot be updated from ' . $status . ' to ' . $newStatus);
                }
            }

            if (isset($input['approval_status'])) {
                $application->update(['approval_status' => $input['approval_status']]);
            }

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
     * @OA\Delete(
     *      path="/api/v1/users/{userId}/dar/applications/{id}/files/{fileId}",
     *      summary="Delete a file associated with a DAR application",
     *      description="Delete a file associated with a DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@destroyFile",
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
     *         name="fileId",
     *         in="path",
     *         description="File id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="File id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found")
     *           ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
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
    public function destroyFile(DeleteUserDataAccessApplicationFile $request, int $userId, int $id, int $fileId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::where('id', $id)->first();
            if (($jwtUser['id'] != $userId) || ($jwtUser['id'] != $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to delete this file.');
            }

            if ($application['submission_status'] === 'SUBMITTED') {
                throw new Exception('Files cannot be deleted after a data access request has been submitted.');
            }

            $answers = DataAccessApplicationAnswer::where('application_id', $id)->get();

            foreach ($answers as $k => $answer) {
                $isFileAnswer = $this->isFileAnswer($answer->answer);
                if (!$isFileAnswer['is_file']) {
                    continue;
                }
                if ($isFileAnswer['multifile']) {
                    $value = $answer->answer['value'];
                    foreach ($value as $i => $a) {
                        if ($a['id'] === $fileId) {
                            unset($value[$i]);
                            DataAccessApplicationAnswer::findOrFail($answer->id)->update([
                                'answer' => ['value' => $value]
                            ]);
                        }
                    }
                } else {
                    if ($answer->answer['value']['id'] === $fileId) {
                        DataAccessApplicationAnswer::where('id', $answer->id)->delete();
                    }
                }
            }

            $file = Upload::where('id', $fileId)->first();

            Storage::disk(env('SCANNING_FILESYSTEM_DISK', 'local_scan') . '_scanned')
                ->delete($file->file_location);

            $file->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' file ' . $fileId . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));

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
     * @OA\Delete(
     *      path="/api/v1/users/{userId}/dar/applications/{id}",
     *      summary="Delete a users DAR application",
     *      description="Delete a users DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@destroy",
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
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success")
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
    public function destroy(DeleteUserDataAccessApplication $request, int $userId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $application = DataAccessApplication::findOrFail($id);
            if (($jwtUser['id'] !== $userId) || ($jwtUser['id'] !== $application->applicant_id)) {
                throw new UnauthorizedException('User does not have permission to use this endpoint to delete this file.');
            }

            if ($application['submission_status'] === 'SUBMITTED') {
                throw new Exception('A data access request cannot be deleted after it has been submitted.');
            }

            TeamHasDataAccessApplication::where('dar_application_id', $id)->delete();
            DataAccessApplicationHasDataset::where('dar_application_id', $id)->delete();
            DataAccessApplicationAnswer::where('application_id', $id)->delete();
            $application->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
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

        $teamIds = TeamHasDataAccessApplication::where('dar_application_id', $id)
            ->select('team_id')
            ->pluck('team_id');

        $teams = Team::whereIn('id', $teamIds)->get();

        $to = [
            'to' => [
                'email' => $user['email'],
                'name' => $user['name'],
            ],
        ];

        $teamNames = array_column($teams->toArray(), 'name');
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
            $teamNotifications = $this->getTeamNotifications($team->id);
            $darManagers = array_merge($darManagers, $teamNotifications);

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

    private function isFileAnswer(array | string $answer): array
    {
        $isFile = false;
        $isMulti = false;

        if (isset($answer['value']) && is_array($answer['value'])) {
            if (isset($answer['value']['filename'])) {
                $isFile = true;
            }

            if (isset($answer['value'][0]['filename'])) {
                $isFile = true;
                $isMulti = true;
            }
        }

        return [
            'is_file' => $isFile,
            'multifile' => $isMulti,
        ];
    }

    private function splitSubmittedApplication(DataAccessApplication $application): void
    {
        $id = $application->id;
        $thd = TeamHasDataAccessApplication::where('dar_application_id', $id)->get()->toArray();
        $datasets = DataAccessApplicationHasDataset::where('dar_application_id', $id)->get();
        $questions = DataAccessApplicationHasQuestion::where('application_id', $id)->get();
        $answers = DataAccessApplicationAnswer::where('application_id', $id)->get();
        $status = DataAccessApplicationStatus::where('application_id', $id)->get();
        $oneTeam = array_pop($thd);

        foreach ($thd as $t) {
            $newApplication = DataAccessApplication::create(
                $this->extractFillables($application)
            );
            TeamHasDataAccessApplication::create([
                'dar_application_id' => $newApplication->id,
                'team_id' => $t['team_id'],
            ]);
            foreach ($datasets as $dataset) {
                DataAccessApplicationHasDataset::create(
                    array_merge(
                        $this->extractFillables($dataset),
                        ['dar_application_id' => $newApplication->id]
                    )
                );
            }
            foreach ($questions as $question) {
                DataAccessApplicationHasQuestion::create(
                    array_merge(
                        $this->extractFillables($question),
                        ['application_id' => $newApplication->id]
                    )
                );
            }
            foreach ($answers as $answer) {
                DataAccessApplicationAnswer::create(
                    array_merge(
                        $this->extractFillables($answer),
                        ['application_id' => $newApplication->id]
                    )
                );
            }
            foreach ($status as $s) {
                DataAccessApplicationStatus::create(
                    array_merge(
                        $this->extractFillables($s),
                        ['application_id' => $newApplication->id]
                    )
                );
            }
        }
        $splitTeams = array_column($thd, 'team_id');
        TeamHasDataAccessApplication::whereIn('team_id', $splitTeams)
            ->where('dar_application_id', $id)
            ->delete();
    }

    private function extractFillables(Model $model): array
    {
        return array_intersect_key($model->toArray(), array_flip($model->getFillable()));
    }

}
