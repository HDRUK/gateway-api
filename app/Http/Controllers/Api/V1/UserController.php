<?php

namespace App\Http\Controllers\Api\V1;

use Hash;
use Config;
use Exception;
use App\Models\User;
use App\Http\Requests\User\GetUser;
use App\Models\UserHasNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\EditUser;
use App\Http\Requests\User\CreateUser;
use App\Http\Requests\User\DeleteUser;
use App\Http\Requests\User\UpdateUser;
use App\Http\Traits\UserTransformation;

class UserController extends Controller
{
    use UserTransformation;

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
     */
    public function index(): mixed
    {
        $users = User::with('teams')->get()->toArray();

        $response = $this->getUsers($users);

        return response()->json([
            'data' => $response
        ]);
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
     */
    public function show(GetUser $request, int $id): mixed
    {
        $users = User::where([
            'id' => $id,
        ])->get();

        if ($users->count()) {
            $userTeam = User::where('id', $id)->with(['teams', 'notifications'])->get()->toArray();
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
     */
    public function store(CreateUser $request): mixed
    {
        try {
            $input = $request->all();

            $array = [
                'name' => $input['firstname'] . " " . $input['lastname'],
                'firstmame' => $input['firstname'],
                'lastname' => $input['lastname'],
                'email' => $input['email'],
                'provider' =>  Config::get('constants.provider.service'),
                'password' => Hash::make($input['password']),
                'sector_id' => $input['sector_id'],
                'organisation' => $input['organisation'],
                'bio' => $input['bio'],
                'domain' => $input['domain'],
                'link' => $input['link'],
                'orcid' => $input['orcid'],
                'contact_feedback' => $input['contact_feedback'],
                'contact_news' => $input['contact_news'],
                'mongo_id' => $input['mongo_id'],
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
     * @OA\Put(
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
     */
    public function update(UpdateUser $request, int $id): mixed
    {
        try {
            $input = $request->all();

            $user = User::findOrFail($id);
            if ($user) {
                $array = [
                    "name" => $input['firstname'] . " " . $input['lastname'],
                    "firstname" => $input['firstname'],
                    "lastname" => $input['lastname'],
                    "email" => $input['email'],
                    'provider' =>  Config::get('constants.provider.service'),
                    'password' => Hash::make($input['password']),
                    "sector_id" => $input['sector_id'],
                    "organisation" => $input['organisation'],
                    "bio" => $input['bio'],
                    "domain" => $input['domain'],
                    "link" => $input['link'],
                    "orcid" => $input['orcid'],
                    "contact_feedback" => $input['contact_feedback'],
                    "contact_news" => $input['contact_news'],  
                    'mongo_id' => $input['mongo_id'],                  
                ];

                $user->update($array);

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

    public function edit(EditUser $request, int $id): mixed
    {
        try {
            $input = $request->all();

            $array = [];
            if (array_key_exists('firstname', $input) && array_key_exists('lastname', $input)) {
                $array['name'] = $input['firstname'] . " " . $input['lastname'];
                $array['firstname'] = $input['firstname'];
                $array['lastname'] = $input['lastname'];
            }
            
            if (array_key_exists('email', $input)) {
                $array['email'] = $input['email'];
            }

            if (array_key_exists('password', $input)) {
                $array['password'] = Hash::make($input['password']);
            }

            if (array_key_exists('sector_id', $input)) {
                $array['sector_id'] = $input['sector_id'];
            }

            if (array_key_exists('organisation', $input)) {
                $array['organisation'] = $input['organisation'];
            }

            if (array_key_exists('bio', $input)) {
                $array['bio'] = $input['bio'];
            }

            if (array_key_exists('domain', $input)) {
                $array['domain'] = $input['domain'];
            }

            if (array_key_exists('link', $input)) {
                $array['link'] = $input['link'];
            }

            if (array_key_exists('orcid', $input)) {
                $array['orcid'] = $input['orcid'];
            }

            if (array_key_exists('contact_feedback', $input)) {
                $array['contact_feedback'] = $input['contact_feedback'];
            }

            if (array_key_exists('contact_news', $input)) {
                $array['contact_news'] = $input['contact_news'];
            }

            if (array_key_exists('mongo_id', $input)) {
                $array['mongo_id'] = $input['mongo_id'];
            }

            User::withTrashed()->where('id', $id)->update($array);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => User::withTrashed()->where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
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
    public function destroy(DeleteUser $request, int $id): mixed
    {
        try {
            UserHasNotification::where('user_id', $id)->delete();
            User::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
