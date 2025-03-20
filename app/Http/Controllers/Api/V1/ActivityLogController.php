<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\ActivityLog\GetActivityLog;
use App\Http\Requests\ActivityLog\EditActivityLog;
use App\Http\Requests\ActivityLog\CreateActivityLog;
use App\Http\Requests\ActivityLog\DeleteActivityLog;
use App\Http\Requests\ActivityLog\UpdateActivityLog;

class ActivityLogController extends Controller
{
    use RequestTransformation;

    /**
     * @OA\Get(
     *      path="/api/v1/activity_logs",
     *      summary="List of system activity logs",
     *      description="Returns a list of activity logs stored on the system",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@index",
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
     *                      @OA\Property(property="event_type", type="string", example="passwordReset"),
     *                      @OA\Property(property="user_type_id", type="integer", example="123"),
     *                      @OA\Property(property="log_type_id", type="integer", example="234"),
     *                      @OA\Property(property="version", type="string", example="1.0.0"),
     *                      @OA\Property(property="html", type="string", example="<b>example string</b>"),
     *                      @OA\Property(property="plain_text", type="string", example="example string"),
     *                      @OA\Property(property="user_id_mongo", type="string", example="2529385fsdfsgs69gs9629se"),
     *                      @OA\Property(property="version_id_mongo", type="string", example="2529385fsdfsgs69gs9629se")
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
            $activityLogs = ActivityLog::getAll('user_id', $jwtUser)->paginate(Config::get('constants.per_page'), ['*'], 'page');

            return response()->json(
                $activityLogs
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Return a single system activity log",
     *      description="Return a single system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@show",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log id",
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
     *                  @OA\Property(property="event_type", type="string", example="passwordReset"),
     *                  @OA\Property(property="user_type_id", type="integer", example="123"),
     *                  @OA\Property(property="log_type_id", type="integer", example="234"),
     *                  @OA\Property(property="version", type="string", example="1.0.0"),
     *                  @OA\Property(property="html", type="string", example="<b>example string</b>"),
     *                  @OA\Property(property="plain_text", type="string", example="example string"),
     *                  @OA\Property(property="user_id_mongo", type="string", example="2529385fsdfsgs69gs9629se"),
     *                  @OA\Property(property="version_id_mongo", type="string", example="2529385fsdfsgs69gs9629se")
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
    public function show(GetActivityLog $request, int $id): JsonResponse
    {
        try {
            $activityLog = ActivityLog::findOrFail($id);
            if ($activityLog) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $activityLog,
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *      path="/api/v1/activity_logs",
     *      summary="Create a new system activity log",
     *      description="Creates a new system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@store",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLog definition",
     *          @OA\JsonContent(
     *              required={"event_type", "user_type_id", "log_type_id", "version", "html", "plain_text"},
     *              @OA\Property(property="id", type="integer", example="123"),
     *              @OA\Property(property="event_type", type="string", example="passwordReset"),
     *              @OA\Property(property="user_type_id", type="integer", example="123"),
     *              @OA\Property(property="log_type_id", type="integer", example="234"),
     *              @OA\Property(property="version", type="string", example="1.0.0"),
     *              @OA\Property(property="html", type="string", example="<b>example string</b>"),
     *              @OA\Property(property="plain_text", type="string", example="example string"),
     *              @OA\Property(property="user_id_mongo", type="string", example="2529385fsdfsgs69gs9629se"),
     *              @OA\Property(property="version_id_mongo", type="string", example="2529385fsdfsgs69gs9629se")
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
    public function store(CreateActivityLog $request): JsonResponse
    {
        try {
            $input = $request->all();

            $activityLog = ActivityLog::create([
                'event_type' => $input['event_type'],
                'user_type_id' => $input['user_type_id'],
                'log_type_id' => $input['log_type_id'],
                'user_id' => $input['user_id'],
                'version' => $input['version'],
                'html' => $input['html'],
                'plain_text' => $input['plain_text'],
                'user_id_mongo' => $input['user_id_mongo'],
                'version_id_mongo' => $input['version_id_mongo'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $activityLog->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Update a system activity log",
     *      description="Update a system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@update",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLog definition",
     *          @OA\JsonContent(
     *              required={"event_type", "user_type_id", "log_type_id", "version", "html", "plain_text"},
     *              @OA\Property(property="id", type="integer", example="123"),
     *              @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="event_type", type="string", example="passwordReset"),
     *              @OA\Property(property="user_type_id", type="integer", example="123"),
     *              @OA\Property(property="log_type_id", type="integer", example="234"),
     *              @OA\Property(property="version", type="string", example="1.0.0"),
     *              @OA\Property(property="html", type="string", example="<b>example string</b>"),
     *              @OA\Property(property="plain_text", type="string", example="example string"),
     *              @OA\Property(property="user_id_mongo", type="string", example="2529385fsdfsgs69gs9629se"),
     *              @OA\Property(property="version_id_mongo", type="string", example="2529385fsdfsgs69gs9629se")
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
     *                  @OA\Property(property="event_type", type="string", example="passwordReset"),
     *                  @OA\Property(property="user_type_id", type="integer", example="123"),
     *                  @OA\Property(property="log_type_id", type="integer", example="234"),
     *                  @OA\Property(property="version", type="string", example="1.0.0"),
     *                  @OA\Property(property="html", type="string", example="<b>example string</b>"),
     *                  @OA\Property(property="plain_text", type="string", example="example string"),
     *                  @OA\Property(property="user_id_mongo", type="string", example="2529385fsdfsgs69gs9629se"),
     *                  @OA\Property(property="version_id_mongo", type="string", example="2529385fsdfsgs69gs9629se")
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
    public function update(UpdateActivityLog $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            ActivityLog::where('id', $id)->update([
                'event_type' => $input['event_type'],
                'user_type_id' => $input['user_type_id'],
                'log_type_id' => $input['log_type_id'],
                'user_id' => $input['user_id'],
                'version' => $input['version'],
                'html' => $input['html'],
                'plain_text' => $input['plain_text'],
                'user_id_mongo' => $input['user_id_mongo'],
                'version_id_mongo' => $input['version_id_mongo'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => ActivityLog::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Edit a system activity log",
     *      description="Edit a system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@edit",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log id",
     *         ),
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="ActivityLog definition",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example="123"),
     *              @OA\Property(property="event_type", type="string", example="passwordReset"),
     *              @OA\Property(property="user_type_id", type="integer", example="123"),
     *              @OA\Property(property="log_type_id", type="integer", example="234"),
     *              @OA\Property(property="version", type="string", example="1.0.0"),
     *              @OA\Property(property="html", type="string", example="<b>example string</b>"),
     *              @OA\Property(property="plain_text", type="string", example="example string"),
     *              @OA\Property(property="user_id_mongo", type="string", example="2529385fsdfsgs69gs9629se"),
     *              @OA\Property(property="version_id_mongo", type="string", example="2529385fsdfsgs69gs9629se")
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
     *                  @OA\Property(property="event_type", type="string", example="passwordReset"),
     *                  @OA\Property(property="user_type_id", type="integer", example="123"),
     *                  @OA\Property(property="log_type_id", type="integer", example="234"),
     *                  @OA\Property(property="version", type="string", example="1.0.0"),
     *                  @OA\Property(property="html", type="string", example="<b>example string</b>"),
     *                  @OA\Property(property="plain_text", type="string", example="example string"),
     *                  @OA\Property(property="user_id_mongo", type="string", example="2529385fsdfsgs69gs9629se"),
     *                  @OA\Property(property="version_id_mongo", type="string", example="2529385fsdfsgs69gs9629se")
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
    public function edit(EditActivityLog $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'event_type',
                'user_type_id',
                'log_type_id',
                'user_id',
                'version',
                'html',
                'plain_text',
                'user_id_mongo',
                'version_id_mongo',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            ActivityLog::where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => ActivityLog::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    /**
     * @OA\Delete(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Delete a system activity log",
     *      description="Delete a system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@destroy",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="activity log id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="activity log id",
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
    public function destroy(DeleteActivityLog $request, int $id): JsonResponse
    {
        try {
            ActivityLog::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
