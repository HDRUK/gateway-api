<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use App\Models\ActivityLog;

use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Requests\EditActivityLog;
use App\Http\Requests\CreateActivityLog;
use App\Http\Requests\DeleteActivityLog;
use App\Http\Requests\UpdateActivityLog;
use App\Exceptions\InternalServerErrorException;

class ActivityLogController extends Controller
{
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
        $activityLogs = ActivityLog::paginate(Config::get('constants.per_page'));
        return response()->json(
            $activityLogs
        );
    }

    /**
     * @OA\Get(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Return a single system activity log",
     *      description="Return a single system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@show",
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
    public function show(Request $request, int $id): JsonResponse
    {
        $activityLog = ActivityLog::findOrFail($id);
        if ($activityLog) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $activityLog,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        throw new NotFoundException();
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
        $activityLog = ActivityLog::create($request->post());
        if ($activityLog) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $activityLog->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        }

        throw new InternalServerErrorException();
    }

    /**
     * @OA\Put(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Update a system activity log",
     *      description="Update a system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@update",
     *      security={{"bearerAuth":{}}},
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
        $activityLog = ActivityLog::findOrFail($id);
        $body = $request->post();
        $activityLog->event_type = $body['event_type'];
        $activityLog->user_type_id = $body['user_type_id'];
        $activityLog->log_type_id = $body['log_type_id'];
        $activityLog->user_id = $body['user_id'];
        $activityLog->version = $body['version'];
        $activityLog->html = $body['html'];
        $activityLog->plain_text = $body['plain_text'];
        $activityLog->user_id_mongo = (isset($body['user_id_mongo']) ? $body['user_id_mongo'] : null);
        $activityLog->version_id_mongo = (isset($body['version_id_mongo']) ? $body['version_id_mongo'] : null);

        if ($activityLog->save()) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $activityLog,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } else {
            throw new InternalServerErrorException();
        }

        throw new NotFoundException();
    }


    /**
     * @OA\Patch(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Edit a system activity log",
     *      description="Edit a system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@edit",
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
     *
     * @param EditActivityLog $request
     * @param integer $id
     * @return JsonResponse
     */
    public function edit(EditActivityLog $request, int $id): JsonResponse
    {
        $activityLog = ActivityLog::findOrFail($id);
        $body = $request->all();

        if (array_key_exists('event_type', $body)) {
            $activityLog->event_type = $body['event_type'];
        }

        if (array_key_exists('user_type_id', $body)) {
            $activityLog->user_type_id = $body['user_type_id'];
        }

        if (array_key_exists('log_type_id', $body)) {
            $activityLog->log_type_id = $body['log_type_id'];
        }

        if (array_key_exists('user_id', $body)) {
            $activityLog->user_id = $body['user_id'];
        }

        if (array_key_exists('version', $body)) {
            $activityLog->version = $body['version'];
        }

        if (array_key_exists('html', $body)) {
            $activityLog->html = $body['html'];
        }

        if (array_key_exists('plain_text', $body)) {
            $activityLog->plain_text = $body['plain_text'];
        }

        if (array_key_exists('user_id_mongo', $body)) {
            $activityLog->user_id_mongo = $body['user_id_mongo'];
        }

        if (array_key_exists('version_id_mongo', $body)) {
            $activityLog->version_id_mongo = $body['version_id_mongo'];
        }

        if ($activityLog->save()) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'body' => $body,
                'data' => $activityLog,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } else {
            throw new InternalServerErrorException();
        }

        throw new NotFoundException();
    }


    /**
     * @OA\Delete(
     *      path="/api/v1/activity_logs/{id}",
     *      summary="Delete a system activity log",
     *      description="Delete a system activity log",
     *      tags={"ActivityLog"},
     *      summary="ActivityLog@destroy",
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
    public function destroy(DeleteActivityLog $request, int $id): JsonResponse
    {
        $activityLog = ActivityLog::findOrFail($id);
        if ($activityLog) {
            if ($activityLog->delete()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            throw new InternalServerErrorException();
        }

        throw new NotFoundException();
    }
}
