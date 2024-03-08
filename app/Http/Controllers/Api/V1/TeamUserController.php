<?php

namespace App\Http\Controllers\Api\V1;

use Auditor;
use Config;
use Exception;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\TeamHasUser;
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

class TeamUserController extends Controller
{
    use TeamTransformation;
    use UserRolePermissions;
    
    private const ROLE_CUSTODIAN_TEAM_ADMIN = 'custodian.team.admin';
    private const ASSIGN_PERMISSIONS_IN_TEAM = [
        'roles.dev.update' => ['developer'],
        'roles.mdm.update' => ['hdruk.dar', 'custodian.metadata.manager'],
        'roles.mde.update' => ['hdruk.dar', 'custodian.metadata.manager', 'metadata.editor'],
        'roles.dar-m.update' => ['custodian.dar.manager'],
        'roles.dar-r.update' => ['custodian.dar.manager', 'dar.reviewer']
    ];

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
        try {
            $input = $request->all();

            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $jwtUserIsAdmin = $jwtUser['is_admin'];
            $jwtUserRolePerms = $jwtUser['role_perms'];

            if (!$jwtUserIsAdmin) {
                $this->checkUserPermissions($input['roles'], $jwtUserRolePerms, $teamId, self::ASSIGN_PERMISSIONS_IN_TEAM);
            }

            $userId = $input['userId'];
            $permissions = $input['roles'];
            $sendEmail = $request->has('email') ? $request->boolean('email') : true;

            $teamHasUsers = $this->teamHasUser($teamId, $userId);
            
            $this->teamUsersHasRoles($teamHasUsers, $permissions, $teamId, $userId, $jwtUser, $sendEmail);

            $this->storeAuditLog($jwtUser["id"], $input['userId'], $teamId, $input, class_basename($this) . '@'.__FUNCTION__);

            return response()->json([
                'message' => 'success',
            ], 201);
        } catch (Exception $e) {
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
        try {
            $input = $request->all();

            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $jwtUserIsAdmin = $jwtUser['is_admin'];
            $jwtUserRolePerms = $jwtUser['role_perms'];

            if (!$jwtUserIsAdmin) {
                $this->checkUserPermissions(array_keys($input['roles']), $jwtUserRolePerms, $teamId, self::ASSIGN_PERMISSIONS_IN_TEAM);
            }

            $res = $this->teamUserRoles($teamId, $userId, $input, $jwtUser);

            $this->updateAuditLog($jwtUser["id"], $userId, $teamId, $input, class_basename($this) . '@'.__FUNCTION__);

            return response()->json([
                'message' => 'success',
                'data' => $res,
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // private function updateAuditLog(int $currentUserId, int $teamId, array $payload, string $actionService)
    // {
    //     foreach ($payload['roles'] as $role)
    //     {
    //         $log = [
    //             'user_id' => $currentUserId,
    //             'target_user_id' => $payload['userId'],
    //             'target_team_id' => $teamId,
    //             'action_type' => 'ASSIGN',
    //             'action_service' => $actionService,
    //             'description' => 'User role "' . $role . '" added',
    //         ];

    //         Auditor::log($log);
    //     }
    // }

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
        try {
            $input = $request->all();

            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
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

            $this->updateBulkAuditLog($jwtUser["id"], $teamId, $input, class_basename($this) . '@'.__FUNCTION__);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $response,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
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

            $this->destroyAuditLog($jwtUser["id"], $userId, $teamId, class_basename($this) . '@'.__FUNCTION__);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (Exception $e) {
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
    private function teamUsersHasRoles(array $teamHasUsers, array $roles, int $teamId, int $userId, array $jwtUser, bool $email): void
    {
        try {
            foreach ($roles as $roleName) {
                $roles = Role::updateOrCreate([
                    'name' => $roleName,
                ]);

                TeamUserHasRole::updateOrCreate([
                    'team_has_user_id' => $teamHasUsers['id'],
                    'role_id' => $roles->id,
                ]);

                // send email - add roles
                if ($email) {
                    $this->sendEmail($roleName, true, $teamId, $userId, $jwtUser);
                }
            }
        } catch (Exception $e) {
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

                $this->sendEmail($roleName, $action, $teamId, $userId, $jwtUser);
                $updatesMade[$roleName] = $action ? true : false;
            }

            return $updatesMade;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function sendEmail(string $role, bool $action, int $teamId, int $userId, array $jwtUser)
    {
        try {
            $assignRemove = $action ? 'assign' : 'remove';
            $role = $role . '.' . $assignRemove;
            $template = EmailTemplate::where('identifier', '=', $role)->first();
            $user = User::where('id', '=', $userId)->first();
            $team = Team::where('id', '=', $teamId)->first();

            $to = [
                'to' => [
                    'email' => $user['email'],
                    'name' => $user['name'],
                ],
            ];

            $userAdmins = $this->listOfAdmin($teamId);
            $userAdminsString = '<ul>';
            if (count($userAdmins)) {
                foreach ($userAdmins as $userAdmin) {
                    $userAdminsString.= '<li>' . $userAdmin . '</li>';
                }
            }
            $userAdminsString.= '</ul>';
            $replacements = [
                '[[USER_FIRSTNAME]]' => $user['firstname'],
                '[[ASSIGNER_NAME]]' => $jwtUser['name'],
                '[[TEAM_NAME]]' => $team['name'],
                '[[CURRENT_YEAR]]' => date("Y"),
                '[[LIST_TEAM_ADMINS]]' => $userAdminsString,
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function listOfAdmin(int $teamId) {
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
            foreach ($payload['roles'] as $role)
            {
                Auditor::log([
                    'user_id' => $currentUserId,
                    'target_user_id' => $userId,
                    'target_team_id' => $teamId,
                    'action_type' => 'ASSIGN',
                    'action_service' => $actionService,
                    'description' => 'User role "' . $role . '" added',
                ]);
            }
        } catch (Exception $e) {
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
            foreach ($payload['roles'] as $role => $action)
            {
                Auditor::log([
                    'user_id' => $currentUserId,
                    'target_user_id' => $userId,
                    'target_team_id' => $teamId,
                    'action_type' => 'UPDATE',
                    'action_service' => $actionService,
                    'description' => 'User role "' . $role . '" ' . ($action ? 'added' : 'removed'),
                ]);
            }
        } catch (Exception $e) {
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
                        'action_service' => $actionService,
                        'description' => 'User role "' . $role . '" ' . ($action ? 'added' : 'removed'),
                    ]);
        
                }
            }
        } catch (Exception $e) {
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
                'action_service' => $actionService,
                'description' => 'User was removed',
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
