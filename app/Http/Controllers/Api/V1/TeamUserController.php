<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Role;
use App\Models\TeamHasUser;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Http\Requests\TeamUser\CreateTeamUser;
use App\Http\Requests\TeamUser\DeleteTeamUser;
use App\Http\Requests\TeamUser\UpdateTeamUser;
use App\Models\TeamUserHasRole;

class TeamUserController extends Controller
{
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

            $uId = $input['userId'];
            $permissions = $input['roles'];

            $teamHasUsers = $this->teamHasUser($teamId, $uId);

            $this->teamUsersHasRoles($teamHasUsers, $permissions);

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

            $this->teamUserRoles($teamId, $userId, $input);

            return response()->json([
                'message' => 'success',
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
                'teamHasUsers' => $teamHasUsers->id,
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
    private function teamUsersHasRoles(array $teamHasUsers, array $roles): void
    {
        try {
            foreach ($roles as $role) {
                $roles = Role::updateOrCreate([
                    'name' => $role,
                ]);

                TeamUserHasRole::updateOrCreate([
                    'team_has_user_id' => $teamHasUsers['id'],
                    'role_id' => $roles->id,
                ]);
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

            foreach ($input['roles'] as $permision => $action) {
                $role = Role::where('name', $permision)->first();

                if ($action) {
                    TeamUserHasRole::updateOrCreate([
                        'team_has_user_id' => $teamHasUsers->id,
                        'role_id' => $role->id,
                    ]);
                } else {
                    TeamUserHasRole::where('team_has_user_id', $teamHasUsers->id)
                        ->where('role_id', $role->id)
                        ->delete();
                }
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
