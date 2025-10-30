<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\Team;
use App\Models\TeamHasUser;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\TeamHasNotification;
use App\Http\Controllers\Controller;
use App\Http\Traits\TeamTransformation;
use App\Models\TeamUserHasNotification;
use App\Http\Requests\TeamNotification\CreateTeamNotification;

class TeamNotificationController extends Controller
{
    use TeamTransformation;

    public function __construct()
    {
        //
    }
    /**
     * @OA\Get(
     *      path="/api/v1/teams/{teamId}/notifications",
     *      operationId="get_team_notification",
     *      tags={"Team-Notifications"},
     *      summary="TeamNotificationController@show",
     *      description="Get Team & user - notifications & Team - notifications",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="teamId",
     *         in="path",
     *         description="team id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *            type="integer",
     *            description="team id",
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example="123"),
     *                  @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                  @OA\Property(property="enabled", type="boolean", example="1"),
     *                  @OA\Property(property="name", type="string", example="someName"),
     *                  @OA\Property(property="allows_messaging", type="boolean", example="1"),
     *                  @OA\Property(property="workflow_enabled", type="boolean", example="1"),
     *                  @OA\Property(property="access_requests_management", type="boolean", example="1"),
     *                  @OA\Property(property="uses_5_safes", type="boolean", example="1"),
     *                  @OA\Property(property="is_admin", type="boolean", example="1"),
     *                  @OA\Property(property="member_of", type="string", example="someOrg"),
     *                  @OA\Property(property="contact_point", type="string", example="someone@mail.com"),
     *                  @OA\Property(property="application_form_updated_by", type="integer", example="555"),
     *                  @OA\Property(property="application_form_updated_on", type="datetime", example="2023-04-11"),
     *                  @OA\Property(property="user", type="object", example="{}"),
     *                  @OA\Property(property="notifications", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
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
    public function show(Request $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $team = Team::where('id', $teamId)->with(['notifications'])->first();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Notification get for teams ' . $teamId,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $this->getTeamNotifications($team, $teamId, $jwtUser['id'])
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/teams/{teamId}/notifications",
     *    operationId="create_and_update_team_notification",
     *    tags={"Team-Notifications"},
     *    summary="TeamNotificationController@store",
     *    description="Team & user - notifications & Team - notifications",
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
     *             @OA\Property(property="user_notification_status", type="boolean", example="true"),
     *             @OA\Property(property="team_notification_status", type="boolean", example="true"),
     *             @OA\Property(property="team_emails", type="array",
     *                @OA\Items(type="string", example="djakubowski@example.org"),
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=201,
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
    public function store(CreateTeamNotification $request, int $teamId)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            // team user has notification
            $this->teamUserNotification($input, $teamId);

            // team has notifications
            $teamNotifications = TeamHasNotification::where('team_id', $teamId)->pluck('notification_id')->all();

            if ($teamNotifications) {
                $this->deleteTeamNotifications($teamId, $teamNotifications);
                $this->createTeamNotifications($input, $teamId);
            } else {
                $this->createTeamNotifications($input, $teamId);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Team Notification for team ' . $teamId . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function teamUserNotification(array $input, int $teamId)
    {
        try {
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $jwtUserId = $jwtUser['id'];

            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $jwtUserId,
            ])->first();

            $teamHasUserId = 0;

            if ($teamHasUsers) {
                $teamHasUserId = $teamHasUsers->id;
            } else {
                $teamHasUserId = TeamHasUser::create([
                    'team_id' => $teamId,
                    'user_id' => $jwtUserId,
                ])->id;
            }

            $teamUserHasNotifications = TeamUserHasNotification::where([
                'team_has_user_id' => $teamHasUserId,
            ])->first();

            if ($teamUserHasNotifications) {
                Notification::where('id', $teamUserHasNotifications->notification_id)->update([
                    'notification_type' => 'team_user_notification',
                    'message' => null,
                    'opt_in' => true,
                    'enabled' => $input['user_notification_status'],
                    'email' => null,
                ]);
            } else {
                $notification = Notification::create([
                    'notification_type' => 'team_user_notification',
                    'message' => null,
                    'opt_in' => true,
                    'enabled' => $input['user_notification_status'],
                    'email' => null,
                ]);

                TeamUserHasNotification::create([
                    'team_has_user_id' => $teamHasUserId,
                    'notification_id' => $notification->id,
                ]);
            }
        } catch (Exception $e) {
            Auditor::log([
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function deleteTeamNotifications(int $teamId, array $teamNotifications)
    {
        try {
            TeamHasNotification::where('team_id', $teamId)->delete();
            foreach ($teamNotifications as $item) {
                Notification::where('id', $item)->delete();
            }
        } catch (Exception $e) {
            Auditor::log([
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }


    private function createTeamNotifications(array $input, int $teamId)
    {
        try {
            Team::where('id', $teamId)->update([
                'notification_status' => $input['team_notification_status'],
            ]);
            foreach ($input['team_emails'] as $item) {
                $notification = Notification::create([
                    'notification_type' => 'team_user_notification',
                    'message' => 'team_user_notification',
                    'opt_in' => true,
                    'enabled' => true,
                    'email' => $item,
                ]);
                TeamHasNotification::create([
                    'team_id' => $teamId,
                    'notification_id' => $notification->id,
                ]);
            }
        } catch (Exception $e) {
            Auditor::log([
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
