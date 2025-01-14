<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\DataAccessTemplate\GetDataAccessTemplate;
use App\Http\Requests\DataAccessTemplate\EditDataAccessTemplate;
use App\Http\Requests\DataAccessTemplate\CreateDataAccessTemplate;
use App\Http\Requests\DataAccessTemplate\DeleteDataAccessTemplate;
use App\Http\Requests\DataAccessTemplate\UpdateDataAccessTemplate;
use App\Models\DataAccessTemplate;
use App\Models\DataAccessTemplateHasQuestion;
use App\Models\QuestionBank;
use App\Models\QuestionHasTeam;

class DataAccessTemplateController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/dar/templates",
     *      summary="List of DAR templates",
     *      description="List of DAR templates",
     *      tags={"DataAccessTemplate"},
     *      summary="DataAccessTemplate@index",
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
     *                      @OA\Property(property="team_id", type="integer", example="1"),
     *                      @OA\Property(property="user_id", type="integer", example="1"),
     *                      @OA\Property(property="published", type="boolean", example="true"),
     *                      @OA\Property(property="locked", type="boolean", example="false"),
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

            $templates = DataAccessTemplate::paginate(
                Config::get('constants.per_page'),
                ['*'],
                'page'
            );

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate get all',
            ]);

            return response()->json(
                $templates
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
     *      path="/api/v1/dar/templates/{id}",
     *      summary="Return a single DAR template",
     *      description="Return a single DAR template",
     *      tags={"DataAccessTemplate"},
     *      summary="DataAccessTemplate@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR template id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR template id",
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
     *                  @OA\Property(property="team_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="published", type="boolean", example="true"),
     *                  @OA\Property(property="locked", type="boolean", example="false"),
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
    public function show(GetDataAccessTemplate $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $template = DataAccessTemplate::with('questions')->findOrFail($id);

            if ($template) {
                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'DataAccessTemplate get ' . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $template,
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
     *      path="/api/v1/dar/templates",
     *      summary="Create a new DAR template",
     *      description="Creates a new DAR template",
     *      tags={"DataAccessTemplate"},
     *      summary="DataAccessTemplate@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessTemplate definition",
     *          @OA\JsonContent(
     *              required={"team_id"},
     *              @OA\Property(property="team_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="published", type="boolean", example="true"),
     *              @OA\Property(property="locked", type="boolean", example="false"),
     *              @OA\Property(property="questions", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="1"),
     *                      @OA\Property(property="guidance", type="string", example="Custom guidance"),
     *                      @OA\Property(property="required", type="boolean", example="true"),
     *                      @OA\Property(property="order", type="integer", example="1"),
     *                  )
     *              ),
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
    public function store(CreateDataAccessTemplate $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $template = DataAccessTemplate::create([
                'user_id' => isset($input['user_id']) ? $input['user_id'] : $jwtUser['id'],
                'team_id' => $input['team_id'],
                'published' => isset($input['published']) ? $input['published'] : false,
                'locked' => isset($input['locked']) ? $input['locked'] : false,
            ]);

            if (isset($input['questions'])) {
                $this->insertTemplateHasQuestions($input['questions'], $template, $input['team_id']);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $template->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $template->id,
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
     *      path="/api/v1/dar/templates/{id}",
     *      summary="Update a system DAR template",
     *      description="Update a system DAR template",
     *      tags={"DataAccessTemplate"},
     *      summary="DataAccessTemplate@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR template id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR template id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessTemplate definition",
     *          @OA\JsonContent(
     *              required={"team_id"},
     *              @OA\Property(property="team_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="1"),
     *              @OA\Property(property="published", type="boolean", example="true"),
     *              @OA\Property(property="locked", type="boolean", example="false"),
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
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="team_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="1"),
     *                  @OA\Property(property="published", type="boolean", example="true"),
     *                  @OA\Property(property="locked", type="boolean", example="false"),
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
    public function update(UpdateDataAccessTemplate $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $template = DataAccessTemplate::findOrFail($id);

            $template->update([
                'user_id' => isset($input['user_id']) ? $input['user_id'] : $jwtUser['id'],
                'team_id' => $input['team_id'],
                'published' => isset($input['published']) ? $input['published'] : $template->published,
                'locked' => isset($input['locked']) ? $input['locked'] : $template->locked,
            ]);

            if (isset($input['questions'])) {
                DataAccessTemplateHasQuestion::where('template_id', $id)->delete();
                $this->insertTemplateHasQuestions($input['questions'], $template, $input['team_id']);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessTemplate::where('id', $id)->first(),
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
     *      path="/api/v1/dar/templates/{id}",
     *      summary="Edit a system DAR template",
     *      description="Edit a system DAR template",
     *      tags={"DataAccessTemplate"},
     *      summary="DataAccessTemplate@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR template id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR template id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="DataAccessTemplate definition",
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
    public function edit(EditDataAccessTemplate $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $template = DataAccessTemplate::findOrFail($id);

            $arrayKeys = [
                'team_id',
                'user_id',
                'published',
                'locked',
            ];
            $array = $this->checkEditArray($input, $arrayKeys);
            $template->update($array);

            if (isset($input['questions'])) {
                DataAccessTemplateHasQuestion::where('template_id', $id)->delete();
                $this->insertTemplateHasQuestions($input['questions'], $template, $input['team_id']);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessTemplate::where('id', $id)->first(),
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
     *      path="/api/v1/dar/templates/{id}",
     *      summary="Delete a system DAR template",
     *      description="Delete a system DAR template",
     *      tags={"DataAccessTemplate"},
     *      summary="DataAccessTemplate@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="DAR template id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="DAR template id",
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
    public function destroy(DeleteDataAccessTemplate $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $template = DataAccessTemplate::findOrFail($id);
            DataAccessTemplateHasQuestion::where('template_id', $template->id)->delete();
            $template->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $id . ' deleted',
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

    private function insertTemplateHasQuestions(array $questions, DataAccessTemplate $template, int $teamId): void
    {
        $count = 1;
        foreach ($questions as $q) {
            $question = QuestionBank::where('id', $q['id'])->with('latestVersion')->first();
            // check access to question
            $teams = QuestionHasTeam::where([
                'team_id' => $teamId,
                'qb_question_id' => $q['id']
            ])->get();

            if (($question->question_type === QuestionBank::CUSTOM_TYPE) && (!count($teams))) {
                throw new Exception('Question with id ' . $q['id'] . ' is not accessible by this team.');
            }

            $isRequired = $question->force_required ? true : $q['required'] ?? false;
            if (($question->allow_guidance_override) && isset($q['guidance'])) {
                $guidance = $q['guidance'];
            } else {
                $questionContent = json_decode($question['latestVersion']['question_json'], true);
                $guidance = $questionContent['guidance'];
            }
            DataAccessTemplateHasQuestion::create([
                'template_id' => $template->id,
                'question_id' => $question->id,
                'guidance' => $guidance,
                'required' => $isRequired,
                'order' => $q['order'] ?? $count,
            ]);
            $count += 1;
        }
    }
}
