<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Role;
use App\Models\User;
use App\Models\UserHasRole;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRole\CreateUserRole;
use App\Http\Requests\UserRole\DeleteUserRole;
use App\Http\Requests\UserRole\UpdateUserRole;

class UserRoleController extends Controller
{
    /**
     * constructor method
     */
    public function __construct()
    {
        //
    }

    /**
     * @OA\Post(
     *    path="/api/v1/users/{userId}/roles",
     *    operationId="create_user_has_roles",
     *    tags={"User-Roles"},
     *    summary="UserRoleController@store",
     *    description="Create user has roles",
     *    security={{"bearerAuth":{}}},
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
     *             @OA\Property(property="roles", type="array",
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
     *            @OA\Property(property="message", type="string", example="success")
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
    public function store(CreateUserRole $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $existsUserRole = UserHasRole::where('user_id', $userId)->count();
            if ($existsUserRole) {
                throw new Exception('User id ' . $userId .
                    ' has roles assigned. To alter the roles assigned to the current user, use other endpoints.');
            }

            $this->assignUserRoles($userId, $input['roles']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User Has Role the following roles have been added for user id ' .
                    $userId . ':' . implode(',', $input['roles']),
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/users/{userId}/roles",
     *    operationId="update_user_has_roles",
     *    tags={"User-Roles"},
     *    summary="UserRoleController@edit",
     *    description="Update user has roles",
     *    security={{"bearerAuth":{}}},
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
     *        response=201,
     *        description="Created",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="success")
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
    public function edit(UpdateUserRole $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $this->updateUserRoles($userId, $input['roles']);

            $user = User::with('roles')->where('id', $userId)->first();

            $addRoles = [];
            $removeRoles = [];
            foreach ($input['roles'] as $key => $value) {
                if ($value) {
                    $addRoles[] = $key;
                }
                if (!$value) {
                    $removeRoles[] = $key;
                }
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EDIT',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User has role The following roles have been changed for user id ' .
                    $userId . ': added ' . implode(',', $addRoles) . ' and removed ' .
                    implode(',', $removeRoles),
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $user,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/users/{userId}/roles",
     *    operationId="delete_user_has_roles",
     *    tags={"User-Roles"},
     *    summary="UserRoleController@destroy",
     *    description="Delete user - roles",
     *    security={{"bearerAuth":{}}},
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
    public function destroy(DeleteUserRole $request, int $userId): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            UserHasRole::where(['user_id' => $userId])->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User has role removed all roles related direct with user id ' . $userId,
            ]);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function assignUserRoles(int $userId, array $roleNames)
    {
        foreach ($roleNames as $roleName) {
            $role = Role::where([ 'name' => $roleName, 'enabled' => 1 ])->first();

            if ($role) {
                UserHasRole::create([ 'user_id' => $userId, 'role_id' => $role->id ]);
            }
        }
    }

    private function updateUserRoles(int $userId, array $roleNames)
    {
        foreach ($roleNames as $key => $value) {
            $role = Role::where([ 'name' => $key, 'enabled' => 1 ])->first();

            if (!$role) {
                continue;
            }

            $exists = UserHasRole::where([ 'user_id' => $userId, 'role_id' => $role->id ])->first();

            // add user-role
            if ($value && !$exists) {
                UserHasRole::create([ 'user_id' => $userId, 'role_id' => $role->id ]);
            }

            // remove user-role
            if (!$value && $exists) {
                UserHasRole::where([ 'user_id' => $userId, 'role_id' => $role->id ])->delete();
            }
        }
    }
}
