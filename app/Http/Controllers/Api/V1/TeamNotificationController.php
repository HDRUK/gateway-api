<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\TeamHasNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeamNotification\CreateTeamNotification;
use App\Http\Requests\TeamNotification\DeleteTeamNotification;
use App\Http\Requests\TeamNotification\UpdateTeamNotification;

class TeamNotificationController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * @OA\Post(
     *    path="/api/v1/teams/{teamId}/notifications",
     *    operationId="create_team_notification",
     *    tags={"Team-Notification"},
     *    summary="TeamNotificationController@storeNotificationTeam",
     *    description="Create a new team - notifications",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="team id" ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="notification_type", type="string", example="applicationSubmitted" ),
     *             @OA\Property( property="message", type="string", example="lorem ipsum" ),
     *             @OA\Property( property="opt_in", type="boolean", example="true" ),
     *             @OA\Property( property="enabled", type="boolean", example="true" ),
     *             @OA\Property( property="email", type="string", example="joe@example.com" ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=201,
     *        description="Created",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="success"),
     *            @OA\Property(property="data", type="integer", example="100")
     *        )
     *    ),
     *    @OA\Response(
     *        response=400,
     *        description="bad request",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="bad request"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     */
    public function storeTeamNotification(CreateTeamNotification $request, int $teamId) 
    {
        try {
            $input = $request->all();

            $notification = Notification::create([
                'notification_type' => $input['notification_type'],
                'message' => $input['message'],
                'opt_in' => $input['opt_in'],
                'enabled' => $input['enabled'],
                'email' => $input['email'],
            ]);

            TeamHasNotification::create([
                'team_id' => $teamId,
                'notification_id' => $notification->id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $notification->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/teams/{teamId}/notifications/{notificationId}",
     *    operationId="update_team_notification",
     *    tags={"Team-Notification"},
     *    summary="TeamNotificationController@updateNotificationTeam",
     *    description="Update team - notifications",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="notificationId",
     *       in="path",
     *       description="notification id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="notification id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="notification_type", type="string", example="applicationSubmitted" ),
     *             @OA\Property( property="message", type="string", example="lorem ipsum" ),
     *             @OA\Property( property="opt_in", type="boolean", example="true" ),
     *             @OA\Property( property="enabled", type="boolean", example="true" ),
     *             @OA\Property( property="email", type="string", example="joe@example.com" ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Created",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="success"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=400,
     *        description="bad request",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="bad request"),
     *        )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     */
    public function updateTeamNotification(UpdateTeamNotification $request, int $teamId, int $notificationId)
    {
        try {
            $input = $request->all();

            Notification::where('id', $notificationId)->update([
                'notification_type' => $input['notification_type'],
                'message' => $input['message'],
                'opt_in' => $input['opt_in'],
                'enabled' => $input['enabled'],
                'email' => $input['email'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Notification::where('id', $notificationId)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/teams/{teamId}/notifications/{notificationId}",
     *    operationId="delete_team_notification",
     *    tags={"Team-Notification"},
     *    summary="TeamNotificationController@destroyNotificationTeam",
     *    description="Delete team - notifications",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="team id",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="notificationId",
     *       in="path",
     *       description="notification id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="notification id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     */
    public function destroyTeamNotification(DeleteTeamNotification $request, int $teamId, int $notificationId)
    {
        try {
            TeamHasNotification::where([
                'team_id' => $teamId,
                'notification_id' => $notificationId,
            ])->delete();
            Notification::where('id', $notificationId)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
