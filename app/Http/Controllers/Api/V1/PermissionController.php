<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\TeamUserHasPermission;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\Permission\GetPermission;
use App\Http\Requests\Permission\EditPermission;
use App\Http\Requests\Permission\CreatePermission;
use App\Http\Requests\Permission\DeletePermission;
use App\Http\Requests\Permission\UpdatePermission;

class PermissionController extends Controller
{
    use RequestTransformation;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/permissions",
     *    operationId="fetch_all_permissions",
     *    tags={"Permissions"},
     *    summary="PermissionController@index",
     *    description="Get All Permissions",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::all()->toArray();

        return response()->json([
            'message' => 'success',
            'data' => $permissions
        ], 200);
    }

    /**
     * @OA\Get(
     *    path="/api/v1/permissions/{id}",
     *    operationId="fetch_permissions",
     *    tags={"Permissions"},
     *    summary="PermissionController@show",
     *    description="Get permission by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="permission id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="permission id",
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
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not found response",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="not found"),
     *          )
     *      )
     * )
     * 
     */
    public function show(GetPermission $request, int $id): JsonResponse
    {
        $tags = Permission::where([
            'id' => $id,
        ])->get();

        if ($tags->count()) {
            return response()->json([
                'message' => 'success',
                'data' => $tags,
            ], 200);
        }

        return response()->json([
            'message' => 'not found',
        ], 404);
    }

    /**
     * @OA\Post(
     *    path="/api/v1/permissions",
     *    operationId="create_permissions",
     *    tags={"Permissions"},
     *    summary="PermissionController@store",
     *    description="Create a new permission",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="type", type="string", example="features"),
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
    public function store(CreatePermission $request): JsonResponse
    {
        try {
            $input = $request->all();

            $permission = Permission::create([
                'role' => $input['role'],
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $permission->id,
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/permissions",
     *    operationId="update_permissions",
     *    tags={"Permissions"},
     *    summary="PermissionController@update",
     *    description="Update permission",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="role", type="string", example="fake_role"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=400,
     *        description="Error",
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
    public function update(UpdatePermission $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();

            Permission::where('id', $id)->update([
                'role' => $input['role'],
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Permission::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/permissions",
     *    operationId="edit_permissions",
     *    tags={"Permissions"},
     *    summary="PermissionController@edit",
     *    description="Edit permission",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="role", type="string", example="fake_role"),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=400,
     *        description="Error",
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
    public function edit(EditPermission $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $arrayKeys = [
                'role',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Permission::where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => Permission::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/permissions/{id}",
     *    operationId="delete_permissions",
     *    tags={"Permissions"},
     *    summary="PermissionController@destroy",
     *    description="Delete permission by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="permission id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="permission id",
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
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error"),
     *        )
     *    )
     * )
     *
     */
    public function destroy(DeletePermission $request, string $id): JsonResponse
    {
        try {
            TeamUserHasPermission::where('permission_id', $id)->delete();
            Permission::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
