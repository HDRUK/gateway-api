<?php

namespace App\Http\Controllers\Api\V1;

use Hash;
use Config;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserLiteRequest;
use App\Http\Traits\UserTransformation;
use Exception;

class UserController extends Controller
{
    use UserTransformation;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/users",
     *    operationId="fetch_all_users",
     *    tags={"Users"},
     *    summary="UserController@index",
     *    description="Get All Users",
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
     * 
     * Get All Users
     *
     * @return mixed
     */
    public function index(): mixed
    {
        $users = User::with('teams')->get()->toArray();

        $response = $this->getUsers($users);

        return response()->json([
            'message' => 'success',
            'data' => $response,
        ], 200);
    }

    /**
     * @OA\Get(
     *    path="/api/v1/users/{id}",
     *    operationId="fetch_users",
     *    tags={"Users"},
     *    summary="UserController@show",
     *    description="Get users by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
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
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="success",
     *          ),
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
     *        response=401,
     *        description="Unauthorized",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *    @OA\Response(
     *        response=404,
     *        description="Not found response",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="not found"),
     *        )
     *    )
     * )
     * 
     * Get Permissions by id
     *
     * @param Request $request
     * @param integer $id
     * @return mixed
     */
    public function show(Request $request, int $id): mixed
    {
        $users = User::where([
            'id' => $id,
        ])->get();

        if ($users->count()) {
            $userTeam = User::where('id', $id)->with('teams')->get()->toArray();
            return response()->json([
                'message' => 'success',
                'data' => $this->getUsers($userTeam),
            ], 200);
        }

        return response()->json([
            'message' => 'not found',
        ], 404);
    }

    /**
     * @OA\Post(
     *    path="/api/v1/users",
     *    operationId="create_users",
     *    tags={"Users"},
     *    summary="UserController@store",
     *    description="Create a new user",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="type",
     *                type="string",
     *                example="features",
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
     * 
     * Create a new user
     *
     * @param UserRequest $request
     * @return mixed
     */
    public function store(UserRequest $request): mixed
    {
        try {
            $input = $request->all();

            $array = [
                "name" => $input['firstname'] . " " . $input['lastname'],
                "firstmame" => $input['firstname'],
                "lastname" => $input['lastname'],
                "email" => $input['email'],
                "provider" =>  Config::get('constants.provider.service'),
                "password" => Hash::make($input['password']),
            ];
            $user = User::create($array);

            return response()->json([
                'message' => 'created',
                'data' => $user->id,
            ], 201);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/users",
     *    operationId="update_users",
     *    tags={"Users"},
     *    summary="UserController@update",
     *    description="Update user",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(
     *                property="enabled",
     *                type="boolean",
     *                example=true,
     *             ),
     *          ),
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(
     *             property="message",
     *             type="string",
     *             example="success",
     *          ),
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
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
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
     * Update user
     *
     * @param UserLiteRequest $request
     * @param integer $id
     * @return mixed
     */
    public function update(UserLiteRequest $request, int $id): mixed
    {
        try {
            $input = $request->all();

            if (!$input) {
                return response()->json([
                    'message' => 'bad request',
                ], 400);
            }

            $checkUser = User::where('id', $id)->get();
            if ($checkUser) {
                $array = [
                    "name" => $input['firstname'] . " " . $input['lastname'],
                    "firstname" => $input['firstname'],
                    "lastname" => $input['lastname'],
                    "email" => $input['email'],
                    "provider" =>  Config::get('constants.provider.service'),
                    "password" => Hash::make($input['password']),
                ];

                if (array_key_exists('passwords', $input)) {
                    $array['password'] = Hash::make($input['password']);
                }

                $user = User::where('id', $id)->update($array);

                return response()->json([
                    'message' => 'success',
                    'data' => $user
                ], 202);
            }

            return response()->json([
                'message' => 'not found',
            ], 404);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="api/v1/users/{id}",
     *    operationId="delete_users",
     *    tags={"Users"},
     *    summary="UserController@destroy",
     *    description="Delete User based in id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="user id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="user id",
     *       )
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       )
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
     *       )
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
    public function destroy(int $id): mixed
    {
        try {
            $users = User::where('id', $id)->get();

            if ($users) {
                User::where('id', $id)->delete();

                return response()->json([
                    'message' => 'success',
                ], 200);
            }

            return response()->json([
                'message' => 'not found',
            ], 404);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
