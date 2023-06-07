<?php

namespace App\Http\Controllers\Api\V1;

use Config;

use Illuminate\Http\Request;

use App\Models\ActivityLogType;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateActivityLogType;

class ActivityLogTypeController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/activity_log_types",
     *      summary="List of system activity log types",
     *      description="Returns a list of activity log types enabled on the system",
     *      tags={"ActivityLogType"},
     *      summary="ActivityLogType@index",
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
        $activityLogTypes = ActivityLogType::paginate(Config::get('constants.per_page'));
        return response()->json(
            $activityLogTypes,
        );
    }

    /**
     * @OA\Get(
     *      path="/api/v1/activity_log_types/{id}",
     *      summary="Return a single system activity log type",
     *      description="Return a single system activity log type",
     *      tags={"ActivityLogType"},
     *      summary="ActivityLogType@show",
     *      security={{"bearerAuth":{}}},
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
    public function show(Request $request, int $id): JsonResponse
    {
        $activityLogType = ActivityLogType::findOrFail($id);
        if ($activityLogType) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $activityLogType,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Post(
     *      path="/api/v1/activity_log_types",
     *      summary="Create a new system activity log type",
     *      description="Creates a new system activity log type",
     *      tags={"ActivityLogType"},
     *      summary="ActivityLogType@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogType definition",
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
    public function store(CreateActivityLogType $request): JsonResponse
    {
        $activityLogType = ActivityLogType::create($request->post());
        if ($activityLogType) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $activityLogType->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
        ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
    }

    /**
     * @OA\Put(
     *      path="/api/v1/activity_log_types/{id}",
     *      summary="Update a system activity log type",
     *      description="Update a system activity log type",
     *      tags={"ActivityLogType"},
     *      summary="ActivityLogType@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLogType definition",
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
    public function update(CreateActivityLogType $request, int $id): JsonResponse
    {
        $activityLogType = ActivityLogType::findOrFail($id);
        $body = $request->post();
        $activityLogType->name = $body['name'];

        if ($activityLogType->save()) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $activityLogType,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } else {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/activity_log_types/{id}",
     *      summary="Delete a system activity log type",
     *      description="Delete a system activity log type",
     *      tags={"ActivityLogType"},
     *      summary="ActivityLogType@destroy",
     *      security={{"bearerAuth":{}}},
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
        $activityLogType = ActivityLogType::findOrFail($id);
        if ($activityLogType) {
            if ($activityLogType->delete()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }
}
