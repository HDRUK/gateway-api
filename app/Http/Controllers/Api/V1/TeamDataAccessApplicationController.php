<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InternalServerErrorException;
use Auditor;
use Config;
use Exception;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Http\Traits\DataAccessApplicationHelpers;
use App\Http\Requests\DataAccessApplication\GetDataAccessApplication;
use App\Http\Requests\DataAccessApplication\GetDataAccessApplicationFile;
use App\Http\Requests\DataAccessApplication\EditDataAccessApplication;
use App\Http\Requests\DataAccessApplication\DeleteDataAccessApplicationFile;
use App\Jobs\SendEmailJob;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationComment;
use App\Models\DataAccessApplicationReview;
use App\Models\DataAccessApplicationStatus;
use App\Models\DataAccessApplicationAnswer;
use App\Models\EmailTemplate;
use App\Models\TeamHasDataAccessApplication;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use File;

class TeamDataAccessApplicationController extends Controller
{
    use RequestTransformation;
    use DataAccessApplicationHelpers;

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/dar/applications",
     *      summary="List of dar applications belonging to a team",
     *      description="List of dar applications belonging to a team",
     *      tags={"TeamDataAccessApplication"},
     *      summary="TeamDataAccessApplication@index",
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
    public function index(Request $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $teamApplications = TeamHasDataAccessApplication::where([
                'team_id' => $teamId,
            ])->select('dar_application_id')
                ->pluck('dar_application_id');

            $applicationIds = DataAccessApplication::whereIn('id', $teamApplications)
                ->where('submission_status', 'SUBMITTED')
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
                $teamId,
                null,
            );

            $projectGroups = $request->boolean('project_groups', true);
            if ($projectGroups) {
                $application = $this->groupApplicationsByProject($applications);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication get all by team',
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
     *    path="/api/v1/teams/{teamId}/dar/applications/count/{field}",
     *    operationId="count_unique_fields_dar_applications",
     *    tags={"TeamDataAccessApplications"},
     *    summary="TeamDataAccessApplicationController@count",
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
    public function count(Request $request, int $teamId, string $field): JsonResponse
    {
        try {
            $applicationIds = TeamHasDataAccessApplication::where('team_id', $teamId)
                ->select('dar_application_id')
                ->pluck('dar_application_id');

            $applications = DataAccessApplication::whereIn('id', $applicationIds)
                ->get();

            if ($field === 'action_required') {
                $counts = $this->actionRequiredCounts($applications);
            } else {
                $counts = array();
                foreach ($applications as $app) {
                    foreach ($app['teams'] as $t) {
                        if ($t->team_id === $teamId) {
                            if (array_key_exists($app[$field], $counts)) {
                                $counts[$app[$field]] += 1;
                            } else {
                                $counts[$app[$field]] = 1;
                            }
                        }
                    }
                }
            }

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Team DAR application count",
            ]);

            return response()->json([
                "data" => $counts
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/dar/applications/count",
     *    tags={"TeamDataAccessApplications"},
     *    summary="TeamDataAccessApplicationController@allCounts",
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
    public function allCounts(Request $request, int $teamId): JsonResponse
    {
        try {
            $applicationIds = TeamHasDataAccessApplication::where('team_id', $teamId)
                ->select('dar_application_id')
                ->pluck('dar_application_id');

            $applications = DataAccessApplication::whereIn('id', $applicationIds)
                ->whereNot('submission_status', 'DRAFT')
                ->get();

            $counts = $this->statusCounts($applications);

            $actionCounts = $this->actionRequiredCounts($applications);
            $counts = array_merge($counts, $actionCounts);
            $counts['ALL'] = count($applications);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Team DAR application count",
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
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}",
     *      summary="Return a single DAR application",
     *      description="Return a single DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="TeamDataAccessApplication@show",
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
     *                  @OA\Property(property="teams", type="array", @OA\Items(
     *                      @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                      @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *                  )
     *              ))
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
    public function show(GetDataAccessApplication $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'view');

            $application = DataAccessApplication::where('id', $id)
                ->with(['questions'])
                ->first();

            $groupArrays = $request->boolean('group_arrays', false);

            $this->getApplicationWithQuestions($application);
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
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}/showHeader",
     *      summary="Get header information about a specific DAR",
     *      description="Get header information about a specific DAR",
     *      tags={"TeamDataAccessApplication"},
     *      summary="TeamDataAccessApplication@showHeader",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                      @OA\Property(property="applicant_id", type="integer", example="1"),
     *                      @OA\Property(property="project_title", type="string", example="A project"),
     *                      @OA\Property(property="application_type", type="string", example="A project"),
     *                      @OA\Property(property="project_id", type="integer", example="43"),
     *                      @OA\Property(property="is_joint", type="boolean", example="false"),
     *                      @OA\Property(property="approval_status", type="string", example="FEEDBACK"),
     *                      @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                      @OA\Property(property="status_review_id", type="integer", example="1"),
     *                      @OA\Property(property="days_since_submission", type="integer", example="1"),
     *                      @OA\Property(property="primary_applicant", type="array", @OA\Items(
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
    public function showApplicationHeader(Request $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        try {
            $result = $this->getDARHeader($id, $teamId, null, $jwtUser);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication get by id',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $result,
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}/download",
     *      summary="Returns a DAR form as a CSV with attached files as a zip",
     *      description="Returns a DAR form as a CSV with attached files as a zip",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@download",
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
    public function download(GetDataAccessApplication $request, int $teamId, int $id): BinaryFileResponse | JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'view');
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication download ' . $id,
            ]);

            $this->checkTeamAccess($teamId, $id, 'view');


            $application = DataAccessApplication::where('id', $id)
            ->with(['questions'])
            ->first();

            $isDraft = $application['submission_status'] === 'DRAFT';
            if ($isDraft) {
                throw new Exception('Files associated with a data access request cannot be viewed when the request is still a draft.');
            }

            $zip = new ZipArchive();

            $zipFilename = "/tmp/" . time() . "_$id.zip";
            if (!$zip->open($zipFilename, ZipArchive::CREATE)) {
                throw new InternalServerErrorException("Zip file creation failed");
            }

            $uploads = Upload::where('entity_id', $id)->get();
            if ($uploads) {
                $files = $uploads->all();

                foreach ($files as $f) {
                    $contents = Storage::disk(config('gateway.scanning_filesystem_disk', 'local_scan') . '_scanned')->get($f->file_location);
                    $fileNameInZip = basename($f->file_location);
                    $zip->addFromString($fileNameInZip, $contents);
                }
            }

            $csvFilename = "/tmp/csv" . time() . "_$id.csv";

            $f = fopen($csvFilename, 'w');

            $this->getApplicationWithQuestions($application);
            $application = $application->toArray();

            $answers = DataAccessApplicationAnswer::where('application_id', $id)->get();

            // Match questions to answers
            foreach ($application['questions'] as $q) {
                foreach ($answers as $ans) {
                    if ($ans['question_id'] == $q['question_id']) {
                        $val = $ans['answer'];
                        // If it's an array it's a file field
                        if (is_array($val)) {
                            // Handle if it contains one or multiple files
                            $filenames = is_array($val['value'][0] ?? null)
                                ? array_column($val['value'], 'filename')
                                : [$val['value']['filename'] ?? null];
                            $val = implode(",", $filenames);
                        }
                        $line = ["question" => $q['title'], "answer" => $val ];
                        fputcsv($f, $line);
                    }
                }
            }
            fclose(stream: $f);
            $zip->addFile($csvFilename, "dar_application.csv");
            $zip->close();
            File::delete($csvFilename);

            $updateArray = array();

            if ($application['submission_status'] === "SUBMITTED" && $application['approval_status'] === null) {
                $updateArray['approval_status'] = "FEEDBACK";
            }

            $application = DataAccessApplication::where('id', $id)
            ->first();

            $application->update($updateArray);

            return response()->download($zipFilename)->deleteFileAfterSend(true);

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
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}/files",
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
    public function showFiles(GetDataAccessApplication $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'view');
            $application = DataAccessApplication::where('id', $id)->first();

            $isDraft = $application['submission_status'] === 'DRAFT';
            if ($isDraft) {
                throw new Exception('Files associated with a data access request cannot be viewed when the request is still a draft.');
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
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}/files/{fileId}/download",
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
     *         name="fileId",
     *         in="path",
     *         description="File uuid",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="string",
     *            description="File uuid",
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
    public function downloadFile(GetDataAccessApplicationFile $request, int $teamId, int $id, string $fileId): StreamedResponse | JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'view');
            $application = DataAccessApplication::where('id', $id)->first();

            $isDraft = $application['submission_status'] === 'DRAFT';
            if ($isDraft) {
                throw new Exception('Files associated with a data access request cannot be downloaded when the request is still a draft.');
            }
            $file = Upload::where('uuid', $fileId)->first();

            // Check the file belongs to the application
            if ($file->entity_id !== $id || $file->entity_type !== 'dataAccessApplication') {
                throw new UnauthorizedException("File does not belong to this application.");
            }

            if ($file) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataAccessApplication ' . $id . ' download file ' . $fileId,
                ]);

                return Storage::disk(config('gateway.scanning_filesystem_disk', 'local_scan') . '_scanned')
                    ->download($file->file_location);
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
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}/answers",
     *      summary="Return answers from a single DAR application",
     *      description="Return answers from a single DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@showAnswers",
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
    public function showAnswers(GetDataAccessApplication $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'view');

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
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}/status",
     *      summary="Return the status history of a single DAR application",
     *      description="Return the status history of a single DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@status",
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
     *                  @OA\Property(property="application_id", type="integer", example="123"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *                  @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
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
    public function status(GetDataAccessApplication $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'view');

            $statuses = DataAccessApplicationStatus::where('application_id', $id)->get();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication status history get ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $statuses,
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
     * @OA\Patch(
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}",
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
     *              @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *              @OA\Property(property="comment", type="string", example="Reason for status change"),
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
    public function edit(EditDataAccessApplication $request, int $teamId, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'edit');

            $application = DataAccessApplication::findOrFail($id);

            $teamHasDar = TeamHasDataAccessApplication::where([
                'team_id' => $teamId,
                'dar_application_id' => $id,
            ])->first();

            $submissionStatusUpdate = $input['submission_status'] ?? null;

            $reviewId = null;
            $review = null;
            if (isset($input['comment'])) {
                if ($submissionStatusUpdate === 'DRAFT') {
                    $review = DataAccessApplicationReview::where('application_id', $id)->first();
                }
                if (!$review) {
                    $review = DataAccessApplicationReview::create([
                        'application_id' => $id,
                        'resolved' => true,
                    ]);
                }
                $reviewId = $review->id;

                DataAccessApplicationComment::create([
                    'review_id' => $reviewId,
                    'team_id' => $teamId,
                    'comment' => $input['comment'],
                ]);
            }

            $originalStatus = $application['approval_status'];
            $newStatus = $input['approval_status'] ?? $originalStatus;

            // Team can only edit statuses of a DAR
            $arrayKeys = [
                'approval_status',
                'submission_status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $array['status_review_id'] = $reviewId;
            $application->update($array);

            if ($newStatus !== $originalStatus) {
                $application = DataAccessApplication::where('id', $id)->first();
                $this->emailStatusNotification($id, $application, $teamId);
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
     *      path="/api/v1/teams/{teamId}/dar/applications/{id}/files/{fileId}",
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
    public function destroyFile(DeleteDataAccessApplicationFile $request, int $teamId, int $id, int $fileId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->checkTeamAccess($teamId, $id, 'delete files from');

            $file = Upload::where('id', $fileId)->first();

            Storage::disk(config('gateway.scanning_filesystem_disk', 'local_scan') . '_scanned')
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

    private function emailStatusNotification(int $id, DataAccessApplication $application, int $teamId): void
    {
        $template = EmailTemplate::where(['identifier' => 'dar.status.researcher'])->first();
        $user = User::where('id', $application->applicant_id)->first();

        $to = [
            'to' => [
                'email' => $user['email'],
                'name' => $user['name'],
            ],
        ];
        $approvalStatus = '';
        foreach ($application['teams'] as $t) {
            if ($t->team_id === $teamId) {
                $approvalStatus = $t->approval_status;
            }
        }
        $status = ucwords(strtolower(str_replace('_', ' ', $approvalStatus)));

        $replacements = [
            '[[USER_FIRST_NAME]]' => $user['firstname'],
            '[[PROJECT_TITLE]]' => $application->project_title,
            '[[APPLICATION_ID]]' => $id,
            '[[STATUS]]' => $status,
            '[[CURRENT_YEAR]]' => date("Y"),
        ];

        SendEmailJob::dispatch($to, $template, $replacements);
    }
}
