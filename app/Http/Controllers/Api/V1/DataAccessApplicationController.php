<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Auditor;

use App\Models\DataAccessApplication;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class DataAccessApplicationController extends Controller
{
    private $arrayKeys = [
        'applicant_id',
        'submission_status',
        'approval_status',
    ];

    /**
     * @OA\Get(
     *      path="/api/v1/dar-access-applications",
     *      summary="List of Data Access Applications",
     *      description="Returns a list of Data Access Applications",
     *      tags={"Data Access Applications"},
     *      summary="DataAccessApplications@index",
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
     *                      @OA\Property(property="applicant_id", type="integer", example="1"),
     *                      @OA\Property(property="submission_status", type="string", example="DRAFT"),
     *                      @OA\Property(property="approval_status", type="string", example="APPROVED")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $dataAccessApplications = DataAccessApplication::all();
            
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dataAccessApplications,
            ], Config::get('statuscodes.STATUS_OK.code')); 
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/dar-access-applications/{id}",
     *      summary="Return a single Data Access Application",
     *      description="Return a single Data Access Application",
     *      tags={"Data Access Application"},
     *      summary="DataAccessApplications@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application ID",
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
     *                  @OA\Property(property="applicant_id", type="integer", example="1"),
     *                  @OA\Property(property="submission_status", type="string", example="DRAFT"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED")
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
            $dataAccessApplication = DataAccessApplication::where('id', $id)->first();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $dataAccessApplication,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/dar-access-applications",
     *      summary="Create a new Data Access Application",
     *      description="Creates a new Data Access Application",
     *      tags={"Data Access Applications"},
     *      summary="DataAccessApplications@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Application definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="applicant_id", type="integer", example="1"),
     *              @OA\Property(property="submission_status", type="string", example="DRAFT"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED")
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

            $dataAccessApplication = DataAccessApplication::create($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $dataAccessApplication->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $dataAccessApplication->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/dar-access-applications/{id}",
     *      summary="Update a Data Access Application",
     *      description="Update a Data Access Application",
     *      tags={"Data Access Applications"},
     *      summary="DataAccessApplications@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Application definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="applicant_id", type="integer", example="1"),
     *              @OA\Property(property="submission_status", type="string", example="DRAFT"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED")
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
     *                  @OA\Property(property="applicant_id", type="integer", example="1"),
     *                  @OA\Property(property="submission_status", type="string", example="DRAFT"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED")
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

            $dataAccessApplication = DataAccessApplication::update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplication::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.message'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/dar-access-applications/{id}",
     *      summary="Update a Data Access Application",
     *      description="Update a Data Access Application",
     *      tags={"Data Access Applications"},
     *      summary="DataAccessApplications@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application ID",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data Access Application definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="applicant_id", type="integer", example="1"),
     *              @OA\Property(property="submission_status", type="string", example="DRAFT"),
     *              @OA\Property(property="approval_status", type="string", example="APPROVED")
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
     *                  @OA\Property(property="applicant_id", type="integer", example="1"),
     *                  @OA\Property(property="submission_status", type="string", example="DRAFT"),
     *                  @OA\Property(property="approval_status", type="string", example="APPROVED")
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

            $dataAccessApplication = DataAccessApplication::update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => DataAccessApplication::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.message'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/dar-access-applications/{id}",
     *      summary="Delete a Data Access Application",
     *      description="Delete a Data Access Application",
     *      tags={"Data Access Application"},
     *      summary="DataAccessApplication@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Data Access Application ID",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="Data Access Application ID",
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

            DataAccessApplication::where('id', $id)->delete();

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_service' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'DataAccessApplication ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
