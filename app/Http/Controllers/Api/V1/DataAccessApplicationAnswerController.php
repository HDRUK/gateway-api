<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;

use App\Models\DataAccessApplicationAnswer;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class DataAccessApplicationAnswerController extends Controller
{
    private $arrayKeys = [
        'question_id',
        'application_id',
        'answer',
        'contributor_id',
    ];

    /**
     * @OA\Get(
     *      path="/api/v1/dar-application-answers",
     *      summary="List of Data Access Application Answers",
     *      description="Returns a list of Data Access Application Answers",
     *      tags={"Data Access Application Answers"},
     *      summary="DataAccessApplicationAnswers@index",
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
     *                      @OA\Property(property="question_id", type="integer", example="1"),
     *                      @OA\Property(property="application_id", type="integer", example="143"),
     *                      @OA\Property(property="answer", type="string", example="{}"),
     *                      @OA\Property(property="contributor_id", type="integer", example="1")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $dataAccessApplicationAnswers = DataAccessApplicationAnswer::all();
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dataAccessApplicationAnswers,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/dar-application-answers/{id}",
     *      summary="Return a single Data Access Application Answer",
     *      description="Return a single Data Access Application Answer",
     *      tags={"Data Access Application Answers"},
     *      summary="DataAccessApplicationAnswers@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application Answer ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application Answer ID",
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
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="application_id", type="integer", example="143"),
     *                  @OA\Property(property="answer", type="string", example="{}"),
     *                  @OA\Property(property="contributor_id", type="integer", example="1")
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
            $dataAccessApplicationAnswer = DataAccessApplicationAnswer::where('id', $id)->first();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dataAccessApplicationAnswer,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/dar-application-answers",
     *      summary="Create a new Data Access Application Answer",
     *      description="Creates a new Data Access Application Answer",
     *      tags={"Data Access Application Answers"},
     *      summary="DataAccessApplicationAnswers@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Application Answer definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="question_id", type="integer", example="1"),
     *              @OA\Property(property="application_id", type="integer", example="143"),
     *              @OA\Property(property="answer", type="string", example="{}"),
     *              @OA\Property(property="contributor_id", type="integer", example="1")
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

            $dataAccessApplicationAnswer = DataAccessApplicationAnswer::create($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationAnswer ' . $dataAccessApplicationAnswer->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $dataAccessApplicationAnswer->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/dar-application-answers/{id}",
     *      summary="Update a Data Access Application Answer",
     *      description="Update a Data Access Application Answer",
     *      tags={"Data Access Application Answer"},
     *      summary="DataAccessApplicationAnswers@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application Answer ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application Answer ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Application Answer definition",
     *          @OA\JsonContent(
    *              @OA\Property(property="question_id", type="integer", example="1"),
     *              @OA\Property(property="application_id", type="integer", example="143"),
     *              @OA\Property(property="answer", type="string", example="{}"),
     *              @OA\Property(property="contributor_id", type="integer", example="1")
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
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="application_id", type="integer", example="143"),
     *                  @OA\Property(property="answer", type="string", example="{}"),
     *                  @OA\Property(property="contributor_id", type="integer", example="1")
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

            DataAccessApplicationAnswer::where('id', $id)->update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationAnswer ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplicationAnswer::where('id', $id)
                    ->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/dar-application-answers/{id}",
     *      summary="Update a Data Access Application Answer",
     *      description="Update a Data Access Application Answer",
     *      tags={"Data Access Application Answer"},
     *      summary="DataAccessApplicationAnswers@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application Answer ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application Answer ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Application Answer definition",
     *          @OA\JsonContent(
    *              @OA\Property(property="question_id", type="integer", example="1"),
     *              @OA\Property(property="application_id", type="integer", example="143"),
     *              @OA\Property(property="answer", type="string", example="{}"),
     *              @OA\Property(property="contributor_id", type="integer", example="1")
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
     *                  @OA\Property(property="question_id", type="integer", example="1"),
     *                  @OA\Property(property="application_id", type="integer", example="143"),
     *                  @OA\Property(property="answer", type="string", example="{}"),
     *                  @OA\Property(property="contributor_id", type="integer", example="1")
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

            $arrayKeys = [
                'question_id',
                'application_id',
                'answer',
                'contributor_id',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);
            DataAccessApplicationAnswer::withTrashed()->where('id', $id)->update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationAnswer ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplicationAnswer::where('id', $id)
                    ->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/dar-application-answers/{id}",
     *      summary="Delete a Data Access Application Answer",
     *      description="Delete a Data Access Application Answer",
     *      tags={"Data Access Application Answers"},
     *      summary="DataAccessApplicationAnswers@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application Answer ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application Answer ID",
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

            DataAccessApplicationAnswer::where('id', $id)->delete();

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplicationAnswer ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
