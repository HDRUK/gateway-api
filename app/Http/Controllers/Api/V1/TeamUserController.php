<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Permission;
use App\Models\TeamHasUser;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Models\TeamUserHasPermission;
use App\Http\Requests\AddTeamUserRequest;
use App\Http\Requests\DeleteTeamUserRequest;
use App\Http\Requests\UpdateTeamUserRequest;

class TeamUserController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * @OA\Post(
     *    path="/api/v1/teams/{teamId}/users",
     *    operationId="create_team_user_perms",
     *    tags={"TeamUserPermission"},
     *    summary="TeamUserController@store",
     *    description="Create a new team - user - permissions",
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
     *             @OA\Property( property="permissions", type="array",   
     *                @OA\Items(
     *                   type="string",
     *                   example="create",
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Created",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="integer", example="100")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="bad request"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     *
     * @param AddTeamUserRequest $request
     * @param integer $teamId
     * @return JsonResponse
     */
    public function store(AddTeamUserRequest $request, int $teamId): JsonResponse
    {
        try {
            $input = $request->all();

            $uId = $input['userId'];
            $permissions = $input['permissions'];

            $teamHasUsers = $this->teamHasUser($teamId, $uId);

            $this->teamUsersHasPermissions($teamHasUsers, $permissions);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function update(UpdateTeamUserRequest $request, int $teamId, int $userId)
    {
        try {
            $input = $request->all();

            $this->teamUserPermissions($teamId, $userId, $input);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function destroy(DeleteTeamUserRequest $request, int $teamId, int $userId)
    {
        try {
            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            if (!$teamHasUsers) {
                throw new NotFoundException();
            }

            TeamUserHasPermission::where([
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
     * Add permissions to user from team
     *
     * @param array $teamHasUsers
     * @param array $permissions
     * @return void
     */
    private function teamUsersHasPermissions(array $teamHasUsers, array $permissions): void
    {
        try {
            foreach ($permissions as $permission) {
                $teamUserPermissions = Permission::where([
                    'role' => $permission,
                ])->first();

                $teamUserHasPermissions = TeamUserHasPermission::where([
                    'team_has_user_id' => $teamHasUsers['id'],
                    'permission_id' => $teamUserPermissions->id,
                ])->first();

                if (!$teamUserHasPermissions) {
                    TeamUserHasPermission::insert([
                        'team_has_user_id' => $teamHasUsers['id'],
                        'permission_id' => $teamUserPermissions->id,
                    ]);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Update or delete permissions for a user with in a team
     *
     * @param integer $teamId
     * @param integer $userId
     * @param array $input
     * @return mixed
     */
    private function teamUserPermissions(int $teamId, int $userId, array $input): mixed
    {
        try {
            $teamHasUsers = TeamHasUser::where([
                'team_id' => $teamId,
                'user_id' => $userId,
            ])->first();

            foreach ($input['permissions'] as $permision => $action) {
                $perm = Permission::where('role', $permision)->first();

                if ($action) {
                    TeamUserHasPermission::updateOrCreate([
                        'team_has_user_id' => $teamHasUsers->id,
                        'permission_id' => $perm->id,
                    ]);
                } else {
                    TeamUserHasPermission::where('team_has_user_id', $teamHasUsers->id)
                        ->where('permission_id', $perm->id)
                        ->delete();
                }
            }

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
