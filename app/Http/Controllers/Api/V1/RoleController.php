<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RoleHasPermission;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Role\GetRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\EditRole;
use App\Exceptions\NotFoundException;
use App\Http\Requests\Role\CreateRole;
use App\Http\Requests\Role\DeleteRole;
use App\Http\Requests\Role\UpdateRole;
use App\Http\Traits\RequestTransformation;

class RoleController extends Controller
{
    use RequestTransformation;
    
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/roles",
     *    operationId="fetch_all_roles",
     *    tags={"Roles"},
     *    summary="RoleController@index",
     *    description="Get All Roles",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data", 
     *             type="array",
     *             @OA\Items(type="object", 
     *                @OA\Property(property="id", type="integer", example="1"),
     *                @OA\Property(property="name", type="string", example="hdruk.superadmin"),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="permissions", type="object", 
     *                   @OA\Property(property="id", type="integer", example="1"),
     *                   @OA\Property(property="role", type="string", example="applications.read"),
     *                   @OA\Property(property="allowed_from_apps", type="integer", example="1"),
     *                   @OA\Property(property="description", type="string", example="null"),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(): JsonResponse
    {
        $roles = Role::with(['permissions'])->paginate(Config::get('constants.per_page'));

        return response()->json(
            $roles
        );
    }

    /**
     * @OA\Get(
     *    path="/api/v1/roles/{id}",
     *    operationId="fetch_roles",
     *    tags={"Roles"},
     *    summary="RoleController@show",
     *    description="Get roles by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="role id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="roles id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data", 
     *             type="array",
     *             @OA\Items(type="object", 
     *                @OA\Property(property="id", type="integer", example="1"),
     *                @OA\Property(property="name", type="string", example="hdruk.superadmin"),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="permissions", type="object", 
     *                   @OA\Property(property="id", type="integer", example="1"),
     *                   @OA\Property(property="role", type="string", example="applications.read"),
     *                   @OA\Property(property="allowed_from_apps", type="integer", example="1"),
     *                   @OA\Property(property="description", type="string", example="null"),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found"),
     *       ),
     *    ),
     * )
     */
    public function show(GetRole $request, int $id): JsonResponse
    {
        try {
            $roles = Role::with(['permissions'])
                ->where(['id' => $id])
                ->get();

            if ($roles->count()) {
                return response()->json([
                    'message' => 'success',
                    'data' => $roles,
                ], 200);
            }

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/roles",
     *    operationId="create_role",
     *    tags={"Roles"},
     *    summary="RoleController@store",
     *    description="Create a new role",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="name", type="string", example="this.is.a.new.role" ),
     *             @OA\Property( property="enabled", type="boolean", example="true" ),
     *             @OA\Property( property="permissions", type="array",   
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
    public function store(CreateRole $request): JsonResponse
    {
        try {
            $input = $request->all();

            $role = Role::create([
                'name' => $input['name'],
                'enabled' => $input['enabled'],
            ]);

            $roleId = $role->id;

            foreach ($input['permissions'] as $permission) {
                $perm = Permission::updateOrCreate([
                    'name' => $permission
                ]);

                RoleHasPermission::create([
                    'role_id' => $roleId,
                    'permission_id' => $perm->id,
                ]);
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $role->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/roles/{id}",
     *    tags={"Roles"},
     *    summary="Update a role",
     *    description="Update a role",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="role id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="role id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="name", type="string", example="this.is.a.new.role" ),
     *             @OA\Property( property="enabled", type="boolean", example="true" ),
     *             @OA\Property( property="permissions", type="array",   
     *                @OA\Items(
     *                   type="string",
     *                   example="create",
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data", 
     *             type="array",
     *             @OA\Items(type="object", 
     *                @OA\Property(property="id", type="integer", example="1"),
     *                @OA\Property(property="name", type="string", example="hdruk.superadmin"),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="permissions", type="object", 
     *                   @OA\Property(property="id", type="integer", example="1"),
     *                   @OA\Property(property="role", type="string", example="applications.read"),
     *                   @OA\Property(property="allowed_from_apps", type="integer", example="1"),
     *                   @OA\Property(property="description", type="string", example="null"),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error")
     *       )
     *    )
     * )
     */
    public function update(UpdateRole $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            Role::where('id', $id)->update([
                'name' => $input['name'],
                'enabled' => $input['enabled'],
            ]);

            RoleHasPermission::where('role_id', $id)->delete();

            foreach ($input['permissions'] as $permission) {
                $perm = Permission::updateOrCreate([
                    'name' => $permission
                ]);

                RoleHasPermission::create([
                    'role_id' => $id,
                    'permission_id' => $perm->id,
                ]);
            }

            return response()->json([
                'message' => 'success',
                'data' => Role::with(['permissions'])->where(['id' => $id])->first()
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/roles/{id}",
     *    tags={"Roles"},
     *    summary="Edit a role",
     *    description="Edit a role",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="role id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="role id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property( property="name", type="string", example="this.is.a.new.role" ),
     *             @OA\Property( property="enabled", type="boolean", example="true" ),
     *             @OA\Property( property="permissions", type="array",   
     *                @OA\Items(
     *                   type="string",
     *                   example="create",
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data", 
     *             type="array",
     *             @OA\Items(type="object", 
     *                @OA\Property(property="id", type="integer", example="1"),
     *                @OA\Property(property="name", type="string", example="hdruk.superadmin"),
     *                @OA\Property(property="enabled", type="boolean", example="1"),
     *                @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                @OA\Property(property="permissions", type="object", 
     *                   @OA\Property(property="id", type="integer", example="1"),
     *                   @OA\Property(property="role", type="string", example="applications.read"),
     *                   @OA\Property(property="allowed_from_apps", type="integer", example="1"),
     *                   @OA\Property(property="description", type="string", example="null"),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error")
     *       )
     *    )
     * )
     */
    public function edit(EditRole $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'name',
                'enabled',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Role::where('id', $id)->update($array);

            if (array_key_exists('permissions', $input)) {
                RoleHasPermission::where('role_id', $id)->delete();

                foreach ($input['permissions'] as $permission) {
                    $perm = Permission::updateOrCreate([
                        'name' => $permission
                    ]);

                    RoleHasPermission::create([
                        'role_id' => $id,
                        'permission_id' => $perm->id,
                    ]);
                }
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Role::with(['permissions'])->where(['id' => $id])->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/roles/{id}",
     *    tags={"Roles"},
     *    summary="Delete a role",
     *    description="Delete a role",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="role id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="role id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success")
     *       ),
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="error")
     *       )
     *    )
     * )
     */
    public function destroy(DeleteRole $request, int $id): JsonResponse
    {
        try {
            RoleHasPermission::where('role_id', $id)->delete();
            Role::where('id', $id)->delete();

            return response()->json([
                'message' => 'success',
            ], 200);

            throw new NotFoundException();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
