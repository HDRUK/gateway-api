<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\TeamHasUser;
use App\Models\Notification;
use App\Models\EmailTemplate;
use App\Models\TeamUserHasRole;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Traits\TeamTransformation;
use App\Models\TeamUserHasNotification;
use App\Http\Traits\UserRolePermissions;
use App\Exceptions\UnauthorizedException;
use App\Http\Requests\TeamUser\CreateTeamUser;
use App\Http\Requests\TeamUser\DeleteTeamUser;
use App\Http\Requests\TeamUser\UpdateTeamUser;
use App\Http\Requests\TeamUser\UpdateBulkTeamUser;
use App\Models\TeamHasNotification;

class TeamUserController extends Controller
{
    use TeamTransformation;
    use UserRolePermissions;

    private const ROLE_CUSTODIAN_TEAM_ADMIN = 'custodian.team.admin';
    private const ASSIGN_PERMISSIONS_IN_TEAM = [
        'roles.dev.update' => [
            'developer',
        ],
        'roles.mdm.update' => [
            'hdruk.dar',
            'custodian.metadata.manager',
        ],
        'roles.mde.update' => [
            'hdruk.dar',
            'custodian.metadata.manager',
            'metadata.editor',
        ],
        'roles.dar-m.update' => [
            'custodian.dar.manager',
        ],
        'roles.dar-r.update' => [
            'custodian.dar.manager',
            'dar.reviewer',
        ],
    ];

    private array $beforeRoleNames = [];
    private array $afterRoleNames = [];
    private array $deleteRoleNames = [];
    private array $addRoleNames = [];
    private array $usersTeamNotifications = [];

    public function __construct()
    {
        //
    }

    /**
     * @OA\Post(
     *    path="/api/v1/teams/{teamId}/users",
     *    operationId="create_team_user_roles",
     *    tags={"Team-User-Role"},
     *    summary="TeamUserController@store",
     *    description="Create a new team - user - roles",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="teamId",
     *       in="path",
     *       description="team id",
     *       required=true,
     *       example="1",
     *       @OA\Schema( type="integer", description="team id" ),
     *    ),
     *    @OA\Parameter(
     *       name="email",
     *       in="query",
     *       description="if the value is false be will not send email",
     *       required=false,
     *       @OA\Schema(type="boolean")
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="userId", type="integer", example="1" ),
     *             @OA\Property( property="roles", type="array",
     *                @OA\Items(
     *                   type="string",
     *                   example="create",
     *                ),
     *             ),
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
    public function store(CreateTeamUser $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $jwtUserIsAdmin = $jwtUser['is_admin'];
            $jwtUserRolePerms = $jwtUser['role_perms'];

            if (!$jwtUserIsAdmin) {
                $this->checkUserPermissions($input['roles'], $jwtUserRolePerms, $teamId, self::ASSIGN_PERMISSIONS_IN_TEAM);
            }

            $userId = $input['userId'];
            $permissions = $input['roles'];
            $sendEmail = $request->has('email') ? $request->boolean('email') : true;

            $teamHasUsers = $this->teamHasUser($teamId, $userId);

            $this->teamUsersHasRoles($teamHasUsers, $permissions, $teamId, $userId, $sendEmail);

            $this->storeAuditLog($jwtUser['id'], $input['userId'], $teamId, $input, class_basename($this) . '@' . __FUNCTION__);

            return response()->json([
                'message' => 'success',
            ], 201);
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
     * @OA\Put(
     *    path="/api/v1/teams/{teamId}/users/{userId}",
     *    operationId="update_team_user_roles",
     *    tags={"Team-User-Role"},
     *    summary="TeamUserController@update",
     *    description="Update team - user - roles",
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
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="user id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="roles", type="object",
     *                @OA\Property(
     *                   property="read",
     *                   type="boolean",
     *                   example=true,
     *                ),
     *                @OA\Property(
     *                   property="create",
     *                   type="boolean",
     *                   example=false,
     *                ),
     *             ),
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
    public function update(UpdateTeamUser $request, int $teamId, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $jwtUserIsAdmin = $jwtUser['is_admin'];
            $jwtUserRolePerms = $jwtUser['role_perms'];

            if (!$jwtUserIsAdmin) {
                $this->checkUserPermissions(
                    array_keys($input['roles']),
                    $jwtUserRolePerms,
                    $teamId,
                    self::ASSIGN_PERMISSIONS_IN_TEAM
                );
            }

            $res = $this->teamUserRoles($teamId, $userId, $input, $jwtUser);

            $this->updateAuditLog($jwtUser["id"], $userId, $teamId, $input, class_basename($this) . '@' . __FUNCTION__);

            return response()->json([
                'message' => 'success',
                'data' => $res,
            ], 200);
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
     * @OA\Patch(
     *    path="/api/v1/teams/{teamId}/roles",
     *    operationId="update_team_user_roles_bulk",
     *    tags={"Team-User-Role"},
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
     *    @OA\RequestBody(
     *        required=true,
     *        @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(
     *                type="array",
     *                @OA\Items(
     *                    type="object",
     *                    @OA\Property(property="userId", type="integer", example=21),
     *                    @OA\Property(
     *                        property="roles",
     *                        type="object",
     *                        @OA\Property(property="custodian.metadata.manager", type="boolean"),
     *                        @OA\Property(property="metadata.editor", type="boolean"),
     *                        @OA\Property(property="dar.reviewer", type="boolean")
     *                    ),
     *                ),
     *            ),
     *        ),
     *    ),
     *     @OA\Response(
     *         response=200,
     *         description="Success response description",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="success"),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="userId", type="integer", example=21),
     *                         @OA\Property(
     *                             property="roles",
     *                             type="object",
     *                             @OA\Property(property="custodian.metadata.manager", type="boolean"),
     *                             @OA\Property(property="metadata.editor", type="boolean"),
     *                             @OA\Property(property="dar.reviewer", type="boolean")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
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
    public function updateBulk(UpdateBulkTeamUser $request, int $teamId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $jwtUserIsAdmin = $jwtUser['is_admin'];
            $jwtUserRolePerms = $jwtUser['role_perms'];

            if (!$jwtUserIsAdmin) {
                $roles = [];
                foreach ($input['payload_data'] as $user) {
                    $roles = array_unique(array_merge($roles, array_keys($user['roles'])));
                }
                $this->checkUserPermissions($roles, $jwtUserRolePerms, $teamId, self::ASSIGN_PERMISSIONS_IN_TEAM);
            }

            $response = [];

            foreach ($input['payload_data'] as $item) {
                $response[] = [
                    'userId' => $item['userId'],
                    'roles' => $this->teamUserRoles($teamId, $item['userId'], $item, $jwtUser),
                ];
            }

            $this->sendEmailUpdateToTeam($teamId);

            $this->updateBulkAuditLog($jwtUser['id'], $teamId, $input, class_basename($this) . '@' . __FUNCTION__);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $response,
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
     * @OA\Delete(
     *    path="/api/v1/teams/{teamId}/users/{userId}",
     *    operationId="delete_team_user_roles",
     *    tags={"Team-User-Role"},
     *    summary="TeamUserController@destroy",
     *    description="Delete team - user - roles",
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
     *       name="userId",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="user id",
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
    public function destroy(DeleteTeamUser $request, int $teamId, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $jwtUserIsAdmin = $jwtUser['is_admin'];
            $jwtUserRolePerms = $jwtUser['role_perms'];

            if (!$this->checkIfAllowDeleteUserFromTeam($teamId, $userId)) {
                throw new UnauthorizedException('You cannot remove last team admin role');
            }

            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            if (!$teamHasUsers) {
                throw new NotFoundException();
            }

            TeamUserHasNotification::where([
                "team_has_user_id" => $teamHasUsers->id,
            ])->delete();

            TeamUserHasRole::where([
                "team_has_user_id" => $teamHasUsers->id,
            ])->delete();

            TeamHasUser::where([
                'team_id' => $teamHasUsers->team_id,
                'user_id' => $teamHasUsers->user_id,
            ])->delete();

            $this->destroyAuditLog($jwtUser['id'], $userId, $teamId, class_basename($this) . '@' . __FUNCTION__);

            return response()->json([
                'message' => 'success',
            ], 200);
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
     * check if the user is the last one with "custodian.team.admin" roles assigned
     *
     * @param integer $teamId
     * @param integer $userId
     * @return boolean
     */
    private function checkIfAllowDeleteUserFromTeam(int $teamId, int $userId): bool
    {
        try {
            $role = Role::where('name', self::ROLE_CUSTODIAN_TEAM_ADMIN)->first();

            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
            ])->get();

            $userIsTeamAdmin = false;
            $countUserTeamAdmin = 0;
            foreach ($teamHasUsers as $teamHasUser) {
                $checkTeamAdminInTeam = TeamUserHasRole::where([
                    'team_has_user_id' => $teamHasUser->id,
                    'role_id' => $role->id,
                ])->first();

                if (!$checkTeamAdminInTeam) {
                    continue;
                }

                if ($checkTeamAdminInTeam && $teamHasUser->user_id === $userId) {
                    $userIsTeamAdmin = true;
                    $countUserTeamAdmin = $countUserTeamAdmin + 1;
                }

                if ($checkTeamAdminInTeam && $teamHasUser->user_id !== $userId) {
                    $countUserTeamAdmin = $countUserTeamAdmin + 1;
                }
            }

            if ($userIsTeamAdmin && $countUserTeamAdmin === 1) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * check and add user to team
     *
     * @param integer $teamId
     * @param integer $userId
     * @return mixed
     */
    private function teamHasUser(int $teamId, int $userId): mixed
    {
        try {
            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            if (!$teamHasUsers) {
                $teamHasUsers = TeamHasUser::create([
                    'team_id' => $teamId,
                    'user_id' => $userId,
                ]);
            }

            return $teamHasUsers->toArray();
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Add roles to user from team
     *
     * @param array $teamHasUsers
     * @param array $roles
     * @return void
     */
    private function teamUsersHasRoles(array $teamHasUsers, array $roles, int $teamId, int $userId, bool $email): void
    {
        try {
            foreach ($roles as $roleName) {
                $role = Role::where(['name' => $roleName])->first();

                if (!is_null($role)) {
                    TeamUserHasRole::updateOrCreate([
                        'team_has_user_id' => $teamHasUsers['id'],
                        'role_id' => $role->id,
                    ]);
                }
            }

            if ($email) {
                $this->sendEmailNewUser($teamId, $userId, $roles);
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Update or delete roles for a user with in a team
     *
     * @param integer $teamId
     * @param integer $userId
     * @param array $input
     * @param array $jwtUser
     * @return mixed
     */
    private function teamUserRoles(int $teamId, int $userId, array $input, array $jwtUser): mixed
    {
        try {
            $this->emailRoles($teamId, $userId, $input);

            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            $updatesMade = [];
            foreach ($input['roles'] as $roleName => $action) {
                $roles = Role::where('name', $roleName)->first();

                if ($action) {
                    TeamUserHasRole::updateOrCreate([
                        'team_has_user_id' => $teamHasUsers->id,
                        'role_id' => $roles->id,
                    ]);
                } else {
                    if ($roleName === self::ROLE_CUSTODIAN_TEAM_ADMIN && count($this->listOfAdmin($teamId)) === 1) {
                        throw new UnauthorizedException('You cannot remove last team admin role');
                    }
                    TeamUserHasRole::where('team_has_user_id', $teamHasUsers->id)
                        ->where('role_id', $roles->id)
                        ->delete();
                }

                $updatesMade[$roleName] = $action ? true : false;
            }

            $this->sendEmailUpdate($teamId, $userId);

            return $updatesMade;
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $userId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function emailRoles(int $teamId, int $userId, array $input)
    {
        $this->beforeRoleNames = [];
        $userTeamNotificationTemp = [];

        $user = User::where('id', $userId)->first();
        if (is_null($user)) {
            throw new NotFoundException('User not found');
        }
        $userTeamNotificationTemp['user_name'] = $user->name;

        $teamHasUsers = TeamHasUser::where([
            'team_id' => $teamId,
            'user_id' => $userId,
        ])->first();

        if (!is_null($teamHasUsers)) {
            $currRoleIds = TeamUserHasRole::where([
                'team_has_user_id' => $teamHasUsers->id,
            ])->get();
            foreach ($currRoleIds as $currRoleId) {
                $role = Role::where('id', $currRoleId->role_id)->first();
                if (!is_null($role)) {
                    $this->beforeRoleNames[] = $role['name'];
                }
            }
        }

        $this->afterRoleNames = [];
        $this->deleteRoleNames = [];
        foreach ($input['roles'] as $roleName => $action) {
            if ($action) {
                $this->afterRoleNames[] = $roleName;
            } else {
                $this->deleteRoleNames[] = $roleName;
            }

            if ($action && !in_array($roleName, $this->beforeRoleNames)) {
                $this->addRoleNames[] = $roleName;
            }
        }

        $userTeamNotificationTemp['user_roles_after'] = $this->afterRoleNames;
        $userTeamNotificationTemp['user_roles_before'] = $this->beforeRoleNames;
        $userTeamNotificationTemp['user_roles_deleted'] = $this->deleteRoleNames;
        $userTeamNotificationTemp['user_roles_added'] = $this->addRoleNames;
        $this->usersTeamNotifications[] = $userTeamNotificationTemp;
        unset($userTeamNotificationTemp);
    }

    private function sendEmailNewUser(int $teamId, int $userId, array $roles)
    {
        try {
            $template = EmailTemplate::where(['identifier' => 'add.new.user.team'])->first();
            $user = User::where('id', '=', $userId)->first();
            $team = Team::where('id', '=', $teamId)->first();

            $to = [
                'to' => [
                    'email' => $user['email'],
                    'name' => $user['name'],
                ],
            ];

            $replacements = [
                '[[USER_FIRSTNAME]]' => $user['firstname'],
                '[[CURRENT_ROLES]]' => $this->stringRoleFullName($roles),
                '[[TEAM_NAME]]' => $team['name'],
                '[[TEAM_ID]]' => $teamId,
                '[[CURRENT_YEAR]]' => date("Y"),
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    public function sendEmailUpdate(int $teamId, int $userId)
    {
        $template = EmailTemplate::where(['identifier' => 'update.roles.team.user'])->first();
        $team = Team::where('id', '=', $teamId)->first();
        $user = User::where('id', '=', $userId)->first();
        $to = [
            'to' => [
                'email' => $user['email'],
                'name' => $user['name'],
            ],
        ];

        $replacements = [
            '[[USER_FIRSTNAME]]' => $user['firstname'],
            '[[TEAM_NAME]]' => $team['name'],
            '[[TEAM_ID]]' => $teamId,
            '[[CURRENT_YEAR]]' => date("Y"),
            '[[CURRENT_ROLES]]' => $this->stringRoleFullName($this->afterRoleNames),
            '[[ADDED_ROLES]]' => $this->stringRoleFullName($this->addRoleNames),
            '[[REMOVED_ROLES]]' => $this->stringRoleFullName($this->deleteRoleNames),
        ];

        SendEmailJob::dispatch($to, $template, $replacements);
    }

    public function sendEmailUpdateToTeam(int $teamId)
    {
        $template = EmailTemplate::where(['identifier' => 'update.roles.team.notifications'])->first();
        $team = Team::where('id', '=', $teamId)->first();
        if (!$team->notification_status) {
            return;
        }

        $teamHasNotifications = TeamHasNotification::where('team_id', $teamId)->get();
        if ($teamHasNotifications->isEmpty()) {
            return;
        }
        $teamNotifications = Notification::whereIn('id', $teamHasNotifications->pluck('notification_id'))->get();
        foreach ($teamNotifications as $notification) {
            if ($notification->email) {
                $to = [
                    'to' => [
                        'email' => $notification['email'],
                        'name' => $team->name,
                    ],
                ];
            } else {
                $user = User::where('id', $notification['user_id'])->first();
                if (is_null($user)) {
                    continue;
                }
                $to = [
                    'to' => [
                        'email' => ($user->preferred_email === 'primary') ? $user->email : $user->secondary_email,
                        'name' => $user->name,
                    ],
                ];
            }

            $replacements = [
                '[[TEAM_NAME]]' => $team->name,
                '[[TEAM_ID]]' => $teamId,
                '[[CURRENT_YEAR]]' => date("Y"),
                '[[USER_CHANGES]]' => $this->stringUserRoleTeamNotifications(),
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        }
    }

    protected function stringUserRoleTeamNotifications()
    {
        $return = '';
        if (!count($this->usersTeamNotifications)) {
            return $return;
        }

        foreach ($this->usersTeamNotifications as $usersTeamNotification) {

            $return .= '<p>' . $usersTeamNotification['user_name'] . '</p>';
            $return .= '<ol>';
            $return .= '   <li>Current roles:</li>';
            $return .= '   <ul>';
            foreach ($usersTeamNotification['user_roles_after'] as $roleNameAfter) {
                $return .= '<li>' . $roleNameAfter . '</li>';
            }
            $return .= '   </ul>';
            $return .= '   <li>Added roles:</li>';
            $return .= '   <ul>';
            foreach ($usersTeamNotification['user_roles_added'] as $roleNameAdded) {
                $return .= '<li>' . $roleNameAdded . '</li>';
            }
            $return .= '   </ul>';
            $return .= '   <li>Removed roles:</li>';
            $return .= '   <ul>';
            foreach ($usersTeamNotification['user_roles_deleted'] as $roleNameDeleted) {
                $return .= '<li>' . $roleNameDeleted . '</li>';
            }
            $return .= '</ol>';

        }

        return $return;
    }

    public function stringRoleFullName(array $roleNames)
    {
        $return = "";
        if (count($roleNames)) {
            $return = '<ul>';
            foreach ($roleNames as $roleName) {
                $role = Role::where(['name' => $roleName])->select(['full_name'])->first();
                $return .= '<li>' . $role->full_name . '</li>';
            }
            $return .= '</ul>';
        }

        return $return;
    }

    private function listOfAdmin(int $teamId)
    {
        try {
            $admins = [];
            $userTeam = Team::where('id', $teamId)->with(['users', 'notifications'])->get()->toArray();
            $team = $this->getTeams($userTeam);

            $users = $team['users'];
            foreach ($users as $user) {
                $userName = $user['name'];
                foreach ($user['roles'] as $role) {
                    if ($role['name'] === self::ROLE_CUSTODIAN_TEAM_ADMIN) {
                        $admins[] = $userName;
                    }
                }
            }

            return $admins;
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

    /**
     * Add Audit Log for store method
     *
     * @param integer $currentUserId
     * @param integer $teamId
     * @param array $payload
     * @param string $actionService
     * @return void
     */
    private function storeAuditLog(int $currentUserId, int $userId, int $teamId, array $payload, string $actionService)
    {
        try {
            foreach ($payload['roles'] as $role) {
                Auditor::log([
                    'user_id' => $currentUserId,
                    'target_user_id' => $userId,
                    'target_team_id' => $teamId,
                    'action_type' => 'ASSIGN',
                    'action_name' => $actionService,
                    'description' => 'User role "' . $role . '" added',
                ]);
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUserId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Add Audit Log for update method
     *
     * @param integer $currentUserId
     * @param integer $teamId
     * @param array $payload
     * @param string $actionService
     * @return void
     */
    private function updateAuditLog(int $currentUserId, int $userId, int $teamId, array $payload, string $actionService)
    {
        try {
            foreach ($payload['roles'] as $role => $action) {
                Auditor::log([
                    'user_id' => $currentUserId,
                    'target_user_id' => $userId,
                    'target_team_id' => $teamId,
                    'action_type' => 'UPDATE',
                    'action_name' => $actionService,
                    'description' => 'User role "' . $role . '" ' . ($action ? 'added' : 'removed'),
                ]);
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUserId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Add Audit Log for updateBulk method
     *
     * @param integer $currentUserId
     * @param integer $teamId
     * @param array $payload
     * @param string $actionService
     * @return void
     */
    private function updateBulkAuditLog(int $currentUserId, int $teamId, array $payload, string $actionService)
    {
        try {
            foreach ($payload['payload_data'] as $item) {
                $userId = $item['userId'];
                $roles = $item['roles'];

                foreach ($roles as $role => $action) {
                    Auditor::log([
                        'user_id' => $currentUserId,
                        'target_user_id' => $userId,
                        'target_team_id' => $teamId,
                        'action_type' => 'UPDATE',
                        'action_name' => $actionService,
                        'description' => 'User role "' . $role . '" ' . ($action ? 'added' : 'removed'),
                    ]);

                }
            }
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUserId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Add Audit Log for destroy method
     *
     * @param integer $currentUserId
     * @param integer $userId
     * @param integer $teamId
     * @param string $actionService
     * @return void
     */
    private function destroyAuditLog(int $currentUserId, int $userId, int $teamId, string $actionService)
    {
        try {
            Auditor::log([
                'user_id' => $currentUserId,
                'target_user_id' => $userId,
                'target_team_id' => $teamId,
                'action_type' => 'REMOVE',
                'action_name' => $actionService,
                'description' => 'User was removed',
            ]);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUserId,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }
}
