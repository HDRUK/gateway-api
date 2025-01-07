<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\DataAccessApplication\GetDataAccessApplication;
use App\Http\Requests\DataAccessApplication\EditDataAccessApplication;
use App\Http\Requests\DataAccessApplication\CreateDataAccessApplication;
use App\Http\Requests\DataAccessApplication\DeleteDataAccessApplication;
use App\Http\Requests\DataAccessApplication\UpdateDataAccessApplication;
use App\Models\DataAccessApplication;
use App\Models\DataAccessApplicationHasDataset;
use App\Models\DataAccessApplicationHasQuestion;
use App\Models\DataAccessTemplate;
use App\Models\Dataset;
use App\Models\Team;

class DataAccessApplicationController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/dar/applications",
     *      summary="List of DAR applications",
     *      description="List of DAR applications",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@index",
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
     *                      @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                      @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $applications = DataAccessApplication::paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication get all',
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
     *      path="/api/v1/dar/applications/{id}",
     *      summary="Return a single DAR application",
     *      description="Return a single DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@show",
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
     *                  @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED"),
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
    public function show(GetDataAccessApplication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $application = DataAccessApplication::where('id', $id)->with('questions')->first();

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
     * @OA\Post(
     *      path="/api/v1/dar/applications",
     *      summary="Create a new DAR application",
     *      description="Creates a new DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessApplication definition",
     *          @OA\JsonContent(
     *              required={},
     *              @OA\Property(property="applicant_id", type="integer", example="1"),
     *              @OA\Property(property="submission_status", type="string", example="SUBMITTED"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED"),
     *              @OA\Property(property="team_ids", type="array", @OA\Items()),
     *              @OA\Property(property="dataset_ids", type="array", @OA\Items()),
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
    public function store(CreateDataAccessApplication $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $application = DataAccessApplication::create([
                'applicant_id' => isset($input['applicant_id']) ? $input['applicant_id'] : $jwtUser['id'],
                'submission_status' => isset($input['submission_status']) ? $input['submission_status'] : 'DRAFT',
            ]);

            // find data provider for each dataset
            $teams = array();
            foreach ($input['dataset_ids'] as $d) {
                $metadata = Dataset::findOrFail($d)->lastMetadata();
                DataAccessApplicationHasDataset::create([
                    'dataset_id' => $d,
                    'dar_application_id' => $application->id
                ]);

                $gatewayId = $metadata['metadata']['summary']['publisher']['gatewayId'];
                // check for primary key or pid match...
                $team = Team::where('id', $gatewayId)->first();
                if (!$team) {
                    $team = Team::where('pid', $gatewayId)->first();
                    if (!$team) {
                        CloudLogger::write([
                            'action_type' => 'CREATE',
                            'action_name' => class_basename($this) . '@' . __FUNCTION__,
                            'description' => 'Unable to create data access application for dataset with id ' . $d . ', no matching team found.',
                        ]);
                        continue;
                    }
                }
                $teams[] = $team;
            }

            // compile questions from each teams template
            $questions = array();
            foreach ($teams as $team) {
                $template = DataAccessTemplate::where([
                    'team_id' => $team->id,
                    'published' => true,
                    'locked' => false
                ])->first();
                if ($template) {
                    $templateQuestions = $template->questions()->get();
                    foreach ($templateQuestions as $q) {
                        $q['team'] = $team->name;
                        if (!isset($questions[$q->question_id])) {
                            $questions[$q->question_id] = [$q];
                        } else {
                            $questions[$q->question_id][] = $q;
                        }
                    }
                }
            }

            // merge the templates including merging of guidance
            $order = 1;
            foreach ($questions as $qId => $question) {
                $required = in_array(true, $question) ? true : false;
                $teams = implode(',', array_column($question, 'team'));

                $guidanceArray = array();
                foreach($question as $q) {
                    if (isset($guidanceArray[$q['guidance']])) {
                        $guidanceArray[$q['guidance']][] = $q['team'];
                    } else {
                        $guidanceArray[$q['guidance']] = [$q['team']];
                    }
                }
                $guidance = '';
                foreach($guidanceArray as $g => $t) {
                    $guidance .= implode(',', $t) . '\n\n' . $g . '\n\n';
                }

                DataAccessApplicationHasQuestion::create([
                    'application_id' => $application->id,
                    'question_id' => $qId,
                    'guidance' => $guidance,
                    'required' => $required,
                    'order' => $order,
                    'teams' => $teams
                ]);
                $order += 1;
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $application->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $application->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
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
     *      path="/api/v1/dar/applications/{id}",
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
    public function update(UpdateDataAccessApplication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $application = DataAccessApplication::findOrFail($id);

            $application->update([
                'applicant_id' => $input['applicant_id'],
                'submission_status' => $input['submission_status'],
                'approval_status' => isset($input['approval_status']) ? $input['approval_status'] : $application->approval_status,
            ]);

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
     *      path="/api/v1/dar/applications/{id}",
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
    public function edit(EditDataAccessApplication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $application = DataAccessApplication::findOrFail($id);

            $arrayKeys = [
                'applicant_id',
                'submission_status',
                'approval_status',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $application->update($array);

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
     *      path="/api/v1/dar/applications/{id}",
     *      summary="Delete a system DAR application",
     *      description="Delete a system DAR application",
     *      tags={"DataAccessApplication"},
     *      summary="DataAccessApplication@destroy",
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
    public function destroy(DeleteDataAccessApplication $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $application = DataAccessApplication::findOrFail($id);
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
}
