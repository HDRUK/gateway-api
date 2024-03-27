<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;

use App\Models\DataAccessTemplate;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class DataAccessTemplateController extends Controller
{
    private $arrayKeys = [
        'team_id',
        'user_id',
        'published',
        'locked',
    ];

    /**
     * @OA\Get(
     *      path="/api/v1/dar-templates",
     *      summary="List of Data Access Templates",
     *      description="Returns a list of Data Access Templates",
     *      tags={"Data Access Templates"},
     *      summary="DataAccessTemplates@index",
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
            $dataAccessTemplates = DataAccessTemplate::all();
            
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dataAccessTemplates,
            ], Config::get('statuscodes.STATUS_OK.code')); 
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/dar-templates/{id}",
     *      summary="Return a single Data Access Template",
     *      description="Return a single Data Access Template",
     *      tags={"Data Access Template"},
     *      summary="DataAccessTemplate@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Template ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Template ID",
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
     *                  @OA\Property(property="user_id", type="integer", example="143"),
     *                  @OA\Property(property="team_id", type="integer", example="241"),
     *                  @OA\Property(property="published", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="integer", example="0")
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
            $dataAccessTemplate = DataAccessTemplate::where('id', $id)->first();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dataAccessTemplate,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/dar-templates",
     *      summary="Create a new Data Access Template",
     *      description="Creates a new Data Access Template",
     *      tags={"Data Access Template"},
     *      summary="DataAccessTemplate@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Template definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="user_id", type="integer", example="143"),
     *              @OA\Property(property="team_id", type="integer", example="241"),
     *              @OA\Property(property="published", type="integer", example="1"),
     *              @OA\Property(property="locked", type="integer", example="0")
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

            $dataAccessTemplate = DataAccessTemplate::create($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $dataAccessTemplate->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $dataAccessTemplate->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/dar-templates/{id}",
     *      summary="Update a Data Access Template",
     *      description="Update a Data Access Template",
     *      tags={"Data Access Template"},
     *      summary="DataAccessTemplate@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Template ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Template ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Template definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="user_id", type="integer", example="143"),
     *              @OA\Property(property="team_id", type="integer", example="241"),
     *              @OA\Property(property="published", type="integer", example="1"),
     *              @OA\Property(property="locked", type="integer", example="0")
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
     *                  @OA\Property(property="user_id", type="integer", example="143"),
     *                  @OA\Property(property="team_id", type="integer", example="241"),
     *                  @OA\Property(property="published", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="integer", example="0")
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

            $dataAccessTemplate = DataAccessTemplate::update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessTemplate::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.message'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/dar-templates/{id}",
     *      summary="Update a Data Access Template",
     *      description="Update a Data Access Template",
     *      tags={"Data Access Template"},
     *      summary="DataAccessTemplate@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Template ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Template ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Template definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="user_id", type="integer", example="143"),
     *              @OA\Property(property="team_id", type="integer", example="241"),
     *              @OA\Property(property="published", type="integer", example="1"),
     *              @OA\Property(property="locked", type="integer", example="0")
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
     *                  @OA\Property(property="user_id", type="integer", example="143"),
     *                  @OA\Property(property="team_id", type="integer", example="241"),
     *                  @OA\Property(property="published", type="integer", example="1"),
     *                  @OA\Property(property="locked", type="integer", example="0")
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

            $dataAccessTemplate = DataAccessTemplate::update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessTemplate::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.message'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/dar-templates/{id}",
     *      summary="Delete a Data Access Template",
     *      description="Delete a Data Access Template",
     *      tags={"Data Access Template"},
     *      summary="DataAccessTemplate@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Template ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Template ID",
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

            DataAccessTemplate::where('id', $id)->delete();

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessTemplate ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
