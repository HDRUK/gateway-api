<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;

use Carbon\Carbon;

use App\Models\Notification;

use App\Http\Requests\NotificationRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/notifications",
     *      summary="List of notifications",
     *      description="Returns a list of notifications enabled on the system",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example="123"),
     *                      @OA\Property(property="created_at", type="datetime", example="2023-04-19 12:00:00"),
     *                      @OA\Property(property="updated_at", type="datetime", example="2023-04-19 12:00:00"),
     *                      @OA\Property(property="notification_type", type="string", example="someType"),
     *                      @OA\Property(property="message", type="string", example="some message"),
     *                      @OA\Property(property="opt_in", type="boolean", example="1"),
     *                      @OA\Property(property="enabled", type="boolean", example="1"),
     *                  )
     *              )
     *          )
     *      )
     * )
     */

    public function index(Request $request)
    {
        $notifications = Notification::where('enabled', 1)->get();
        return response()->json([
            'message' => Config::get('statuscodes.STATUS_OK.message'),
            'data' => $notifications
        ], Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * @OA\Get(
     *      path="/api/v1/notifications/{id}",
     *      summary="Return a single notification",
     *      description="Return a single notification",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="notification_type", type="string", example="someType"),
     *                  @OA\Property(property="message", type="string", example="some message"),
     *                  @OA\Property(property="opt_in", type="boolean", example="1"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
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
    public function show(Request $request, int $id)
    {
        $notification = Notification::findOrFail($id);
        if ($notification) {
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $notification,
            ], Config::get('statuscodes.STATUS_OK.code'));
        }

        return response()->json([
            'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message')
        ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
    }

    /**
     * @OA\Post(
     *      path="/api/v1/notifications",
     *      summary="Create a new notification",
     *      description="Creates a new notification",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Notification definition",
     *          @OA\JsonContent(
     *              required={"notification_type", "message", "opt_in", "enabled"},
     *              @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *              @OA\Property(property="message", type="string", example="your message here"),
     *              @OA\Property(property="opt_in", type="boolean", example="1"),
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
    public function store(NotificationRequest $request)
    {
        try {
            $notification = Notification::create($request->post());
            if ($notification) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                    'data' => $notification->id,
                ], Config::get('statuscodes.STATUS_CREATED.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
            ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *      path="/api/v1/notifications/{id}",
     *      summary="Update a notification",
     *      description="Update a notification",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Notification definition",
     *          @OA\JsonContent(
     *              required={"notification_type", "message", "opt_in", "enabled"},
     *              @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *              @OA\Property(property="message", type="string", example="your message here"),
     *              @OA\Property(property="opt_in", type="boolean", example="1"),
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
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-19 12:00:00"),
     *                  @OA\Property(property="notification_type", type="string", example="applicationSubmitted"),
     *                  @OA\Property(property="message", type="string", example="your message here"),
     *                  @OA\Property(property="opt_in", type="boolean", example="1"),
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
    public function update(NotificationRequest $request, int $notification)
    {
        try {
            $notification = Notification::findOrFail($notification);
            $body = $request->post();
            $notification->notification_type = $body['notification_type'];
            $notification->message = $body['message'];
            $notification->opt_in = $body['opt_in'];
            $notification->enabled = $body['enabled'];

            if ($notification->save()) {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_OK.message'),
                    'data' => $notification,
                ], Config::get('statuscodes.STATUS_OK.code'));
            } else {
                return response()->json([
                    'message' => Config::get('statuscodes.STATUS_SERVER_ERROR.message'),
                ], Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_NOT_FOUND.message'),
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/notifications/{id}",
     *      summary="Delete a notification",
     *      description="Delete a notification",
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
    public function destroy(Request $request, int $notification)
    {
        $notification = Notification::findOrFail($notification);
        if ($notification) {
            $notification->deleted_at = Carbon::now();
            $notification->enabled = false;
            if ($notification->save()) {
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
