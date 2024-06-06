<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ActivityLogUserType;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Traits\RequestTransformation;
use App\Exceptions\InternalServerErrorException;
use App\Http\Requests\ActivityLogUserType\GetActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\CreateActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\DeleteActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\EditActivityLogUserType;
use App\Http\Requests\ActivityLogUserType\UpdateActivityLogUserType;

class ActivityLogUserTypeController extends Controller
{
    use RequestTransformation;
    
    /**
     * @OA\Get(
     *      path="/api/v1/activity_log_user_types",
     *      summary="List of system activity log user types",
     *      description="Returns a list of activity log user types enabled on the system",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@index",
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
     *                      @OA\Property(property="name", type="string", example="Name"),
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

            $activityLogUserTypes = ActivityLogUserType::paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'GET',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Activity Log User Type get all",
            ]);

            return response()->json(
                $activityLogUserTypes
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Return a single system activity log user type",
     *      description="Return a single system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
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
     *                  @OA\Property(property="name", type="string", example="Name"),
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
    public function show(GetActivityLogUserType $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $activityLogUserType = ActivityLogUserType::findOrFail($id);
            if ($activityLogUserType) {

                Auditor::log([
                    'user_id' => $jwtUser['id'],
                    'action_type' => 'GET',
                    'action_service' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Activity Log User Type get " . $id,
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $activityLogUserType,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }
    
            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/activity_log_user_types",
     *      summary="Create a new system activity log user type",
     *      description="Creates a new system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogUserTypes definition",
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", example="Name"),
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
    public function store(CreateActivityLogUserType $request): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $activityLogUserType = ActivityLogUserType::create($request->post());
            if ($activityLogUserType) {
                Auditor::log([
                    'user_id' => $jwtUser['id'],
                    'action_type' => 'CREATE',
                    'action_service' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Activity Log User Type " . $activityLogUserType->id . " created",
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                    'data' => $activityLogUserType->id,
                ], Config::get('statuscodes.STATUS_CREATED.code'));
            }
    
            throw new InternalServerErrorException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Update a system activity log user type",
     *      description="Update a system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogUserTypes definition",
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", example="Name"),
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
     *                  @OA\Property(property="name", type="string", example="Name"),
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
    public function update(UpdateActivityLogUserType $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $activityLogUserType = ActivityLogUserType::findOrFail($id);
            $body = $request->post();
            $activityLogUserType->name = $body['name'];
    
            if ($activityLogUserType->save()) {
                Auditor::log([
                    'user_id' => $jwtUser['id'],
                    'action_type' => 'UPDATE',
                    'action_service' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "Activity Log User Type " . $id . " updated",
                ]);

                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $activityLogUserType,
                ], Config::get('statuscodes.STATUS_OK.code'));
            } else {
                throw new InternalServerErrorException();
            }
    
            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Edit a system activity log user type",
     *      description="Edit a system activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogUserTypes definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Name"),
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
     *                  @OA\Property(property="name", type="string", example="Name"),
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
     *
     * @param EditActivityLogUserType $request
     * @param integer $id
     * @return JsonResponse
     */
    public function edit(EditActivityLogUserType $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $arrayKeys = [
                'name',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            ActivityLogUserType::where('id', $id)->update($array);

            Auditor::log([
                'user_id' => $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_service' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Activity Log User Type " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => ActivityLogUserType::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/activity_log_user_types/{id}",
     *      summary="Delete a system activity log user type",
     *      description="Delete a system  activity log user type",
     *      tags={"ActivityLogUserType"},
     *      summary="ActivityLogUserType@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log user type id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log user type id",
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
    public function destroy(DeleteActivityLogUserType $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $activityLogUserType = ActivityLogUserType::findOrFail($id);
            if ($activityLogUserType) {
                if ($activityLogUserType->delete()) {
                    Auditor::log([
                        'user_id' => $jwtUser['id'],
                        'action_type' => 'DELETE',
                        'action_service' => class_basename($this) . '@'.__FUNCTION__,
                        'description' => "Activity Log User Type " . $id . " deleted",
                    ]);

                    
                    return response()->json([
                        'message' => Config::get('statuscodes.STATUS_OK.message'),
                    ], Config::get('statuscodes.STATUS_OK.code'));
                }
    
                throw new InternalServerErrorException();
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
