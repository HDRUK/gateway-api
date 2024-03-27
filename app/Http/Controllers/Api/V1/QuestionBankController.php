<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class QuestionBankController extends Controller
{
    private $arrayKeys = [
        'section_id',
        'user_id',
        'team_id',
        'default',
        'locked',
        'required',
        'question_json',
    ];

    /**
     * @OA\Get(
     *      path="/api/v1/questionbanks",
     *      summary="List of system Question Banks",
     *      description="Returns a list of Question Banks enabled on the system",
     *      tags={"Question Bank"},
     *      summary="QuestionBank@index",
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
     *                      @OA\Property(property="deleted_at", type="datetime", example="null"),
     *                      @OA\Property(property="section_id", type="integer", example="1"),
     *                      @OA\Property(property="user_id", type="integer", example="143"),
     *                      @OA\Property(property="team_id", type="integer", example="241"),
     *                      @OA\Property(property="default", type="integer", example="1"),
     *                      @OA\Property(property="locked", type="integer", example="0"),
     *                      @OA\Property(property="required", type="integer", example="1"),
     *                      @OA\Property(property="question_json", type="string", example="{}")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $questionBanks = QuestionBank::all();
            
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $questionBanks,
            ], Config::get('statuscodes.STATUS_OK.code')); 
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/questionbanks/{id}",
     *      summary="Return a single system Question Bank",
     *      description="Return a single system Question Bank",
     *      tags={"Question Bank"},
     *      summary="QuestionBank@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Question Bank ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Question Bank ID",
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
     *                  @OA\Property(property="deleted_at", type="datetime", example="null"),
     *                  @OA\Property(property="section_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="143"),
     *                  @OA\Property(property="team_id", type="integer", example="241"),
     *                  @OA\Property(property="default", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="integer", example="0"),
     *                  @OA\Property(property="required", type="integer", example="1"),
     *                  @OA\Property(property="question_json", type="string", example="{}")
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
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $questionBank = QuestionBank::where('id', $id)->first();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $questionBank,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/questionbanks",
     *      summary="Create a new Question Bank",
     *      description="Creates a new Question Bank",
     *      tags={"Question Bank"},
     *      summary="QuestionBank@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Question Bank definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="143"),
     *              @OA\Property(property="team_id", type="integer", example="241"),
     *              @OA\Property(property="default", type="integer", example="1"),
     *              @OA\Property(property="locked", type="integer", example="0"),
     *              @OA\Property(property="required", type="integer", example="1"),
     *              @OA\Property(property="question_json", type="string", example="{}")
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
    public function store(Request $request): JsonResponse
    {
        try {
            $input = $request->all();

            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $array = $this->checkEditArray($input, $this->arrayKeys);

            $questionBank = QuestionBank::create($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $questionBank->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $questionBank->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/questionbanks/{id}",
     *      summary="Update a Question Bank",
     *      description="Update a Question Bank",
     *      tags={"Question Bank"},
     *      summary="QuestionBank@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Question Bank ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Question Bank ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Question Bank definition",
     *          @OA\JsonContent(
     *              required={"name", "enabled"},
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="143"),
     *              @OA\Property(property="team_id", type="integer", example="241"),
     *              @OA\Property(property="default", type="integer", example="1"),
     *              @OA\Property(property="locked", type="integer", example="0"),
     *              @OA\Property(property="required", type="integer", example="1"),
     *              @OA\Property(property="question_json", type="string", example="{}")
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
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="null"),
     *                  @OA\Property(property="section_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="143"),
     *                  @OA\Property(property="team_id", type="integer", example="241"),
     *                  @OA\Property(property="default", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="integer", example="0"),
     *                  @OA\Property(property="required", type="integer", example="1"),
     *                  @OA\Property(property="question_json", type="string", example="{}")
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
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $array = $this->checkEditArray($input, $this->arrayKeys);

            $questionBank = QuestionBank::update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => QuestionBank::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.message'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/questionbanks/{id}",
     *      summary="Update a Question Bank",
     *      description="Update a Question Bank",
     *      tags={"Question Bank"},
     *      summary="QuestionBank@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Question Bank ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Question Bank ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Question Bank definition",
     *          @OA\JsonContent(
     *              required={"name", "enabled"},
     *              @OA\Property(property="section_id", type="integer", example="1"),
     *              @OA\Property(property="user_id", type="integer", example="143"),
     *              @OA\Property(property="team_id", type="integer", example="241"),
     *              @OA\Property(property="default", type="integer", example="1"),
     *              @OA\Property(property="locked", type="integer", example="0"),
     *              @OA\Property(property="required", type="integer", example="1"),
     *              @OA\Property(property="question_json", type="string", example="{}")
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
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                  @OA\Property(property="deleted_at", type="datetime", example="null"),
     *                  @OA\Property(property="section_id", type="integer", example="1"),
     *                  @OA\Property(property="user_id", type="integer", example="143"),
     *                  @OA\Property(property="team_id", type="integer", example="241"),
     *                  @OA\Property(property="default", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="integer", example="0"),
     *                  @OA\Property(property="required", type="integer", example="1"),
     *                  @OA\Property(property="question_json", type="string", example="{}")
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
    public function edit(Request $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $array = $this->checkEditArray($input, $this->arrayKeys);

            $questionBank = QuestionBank::update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => QuestionBank::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.message'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/questionbanks/{id}",
     *      summary="Delete a Question Bank",
     *      description="Delete a Question Bank",
     *      tags={"Question Bank"},
     *      summary="QuestionBank@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Question Bank ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Question Bank ID",
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
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            QuestionBank::where('id', $id)->delete();

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'QuestionBank ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
