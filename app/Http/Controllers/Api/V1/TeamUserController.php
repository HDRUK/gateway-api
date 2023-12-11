<?php

namespace App\Http\Controllers\Api\V1;

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
use App\Exceptions\UnauthorizedException;
use App\Http\Requests\TeamUser\CreateTeamUser;
use App\Http\Requests\TeamUser\DeleteTeamUser;
use App\Http\Requests\TeamUser\UpdateTeamUser;

class TeamUserController extends Controller
{
    use TeamTransformation;
    
    private $roleAdmin = 'custodian.team.admin';

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

            $userId = $input['userId'];
            $permissions = $input['roles'];
            $sendEmail = $request->has('email') ? $request->boolean('email') : true;

            $teamHasUsers = $this->teamHasUser($teamId, $userId);

            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            
            $this->teamUsersHasRoles($teamHasUsers, $permissions, $teamId, $userId, $jwtUser, $sendEmail);

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

            $res = $this->teamUserRoles($teamId, $userId, $input);

            return response()->json([
                'message' => 'success',
                'data' => $res,
            ], 200);
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
            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            if (!$teamHasUsers) {
                throw new NotFoundException();
            }

            TeamUserHasRole::where([
                "team_has_user_id" => $teamHasUsers->id,
            ])->delete();

            TeamHasUser::where([
                'team_id' => $teamHasUsers->team_id,
                'user_id' => $teamHasUsers->user_id,
            ])->delete();

            return response()->json([
                'message' => 'success',
            ], 200);
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
     * @return mixed
     */
    private function teamUserRoles(int $teamId, int $userId, array $input): mixed
    {
        try {
            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $updatesMade = [];
            foreach ($input['roles'] as $roleName => $action) {
                $roles = Role::where('name', $roleName)->first();

                $notifyChange = false;                
                if ($action) {
                    $teamUser = TeamUserHasRole::updateOrCreate([
                        'team_has_user_id' => $teamHasUsers->id,
                        'role_id' => $roles->id,
                    ]);
                    //need to make sure the values were actually changed (or created)
                    // before sending an email.. otherwise email will be sent even when not changed
                    if($teamUser->wasRecentlyCreated ||  $teamUser->wasChanged()){
                        $notifyChange = true;
                    }

                } else {
                    if ($roleName === $this->roleAdmin && count($this->listOfAdmin($teamId)) === 1) {
                        throw new UnauthorizedException('You cannot remove last team admin role');
                    }
                    TeamUserHasRole::where('team_has_user_id', $teamHasUsers->id)
                        ->where('role_id', $roles->id)
                        ->delete();
                    $notifyChange = true;
                }

                if($notifyChange){
                    $this->sendEmail($roleName, $action, $teamId, $userId, $jwtUser);
                    $updatesMade[$roleName] = $action ? 'assign' : 'remove';
                }
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
            $userAdminsString.= '<ul>';
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
                    if ($role['name'] === $this->roleAdmin) {
                        $admins[] = $userName;
                    }
                }
            }

            return $admins;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } 
    }
}
