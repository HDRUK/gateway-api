<?php

namespace App\Http\Controllers\Api\V1;

use Hash;
use Config;
use Auditor;
use Exception;
use App\Models\User;
use App\Models\UserHasRole;
use Illuminate\Http\Request;
use App\Http\Requests\User\GetUser;
use App\Models\UserHasNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\EditUser;
use App\Http\Traits\HubspotContacts;
use App\Exceptions\NotFoundException;
use App\Http\Requests\User\IndexUser;
use App\Http\Requests\User\CreateUser;
use App\Http\Requests\User\DeleteUser;
use App\Http\Requests\User\UpdateUser;
use App\Http\Traits\UserTransformation;
use App\Http\Traits\RequestTransformation;

class UserController extends Controller
{
    use UserTransformation, RequestTransformation, HubspotContacts;

    /**
     * @OA\Get(
     *    path="/api/v1/users",
     *    operationId="fetch_all_users",
     *    tags={"Users"},
     *    summary="UserController@index",
     *    description="Get All Users",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="filterNames",
     *       in="query",
     *       description="Three or more characters to filter users names by",
     *       example="abc",
     *       @OA\Schema(
     *          type="string",
     *          description="Three or more characters to filter users names by",
     *       ),
     *    ),
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
    public function index(IndexUser $request): mixed
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $response = [];
            if (count($jwtUser)) { //should really always be a jwtUser
                $userIsAdmin = (bool) $jwtUser['is_admin'];
                if($userIsAdmin){ // if it's the superadmin return a bunch of information
                    $users = User::with(
                        'roles',
                        'roles.permissions',
                        'teams',
                        'notifications'
                    )->get()->toArray();
                    $response = $this->getUsers($users);
                } else { 
                    // otherwise, for now, just return the ids and names 
                    // (filtered if appropriate)
                    if ($request->has('filterNames')) {
                        $chars = $request->query('filterNames');
                        $response = User::where('name', 'like', '%' . $chars . '%')
                            ->select(['id', 'name'])
                            ->get()
                            ->toArray();
                    } else {
                        $response = User::select(
                            'id','name'
                        )->get()->toArray();
                    }
                }
            }

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "User get all",
            ]);

            return response()->json([
                'data' => $response,
            ], Config::get('statuscodes.STATUS_OK.code'));    
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $users = User::where([
                'id' => $id,
            ])->get();
    
            if ($users->count()) {
                $userTeam = User::where('id', $id)->with(
                    'roles',
                    'roles.permissions',
                    'teams',
                    'notifications'
                )->get()->toArray();

                Auditor::log([
                    'user_id' => (int) $jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "User get " . $id,
                ]);

                return response()->json([
                    'message' => 'success',
                    'data' => $this->getUsers($userTeam),
                ], 200);
            }
    
            return response()->json([
                'message' => 'not found',
            ], 404);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $array = [
                'name' => $input['firstname'] . " " . $input['lastname'],
                'firstname' => $input['firstname'],
                'lastname' => $input['lastname'],
                'email' => $input['email'],
                'secondary_email' => array_key_exists('secondary_email', $input) ? $input['secondary_email'] : NULL,
                'preferred_email' => array_key_exists('preferred_email', $input) ? $input['preferred_email'] : 'primary',
                'provider' =>  array_key_exists('provider', $input) ? $input['provider'] : Config::get('constants.provider.service'),
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
                'mongo_object_id' => $input['mongo_object_id'],
                'terms' => array_key_exists('terms', $input) ? $input['terms'] : 0,
            ];

            // TODO - At this stage we may want to use the is_admin
            // model flag to signify creation of HDR specific users
            // which use user_has_roles relation to determine their
            // role/permissions outside of a team

            $arrayUserNotification = array_key_exists('notifications', $input) ? $input['notifications'] : [];
            
            $user = User::create($array);

            if ($user) {
                foreach ($arrayUserNotification as $value) {
                    UserHasNotification::updateOrCreate([
                        'user_id' => (int) $user->id,
                        'notification_id' => (int) $value,
                    ]);
                }
            } else {
                throw new NotFoundException();
            }

            $this->updateOrCreateContact($user->id);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "User " . $user->id . " created",
            ]);

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
     *    path="/api/v1/users/{id}",
     *    operationId="update_users",
     *    tags={"Users"},
     *    summary="UserController@update",
     *    description="Update user",
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
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $user = User::findOrFail($id);
            if ($user) {
                $array = [
                    "name" => $input['firstname'] . " " . $input['lastname'],
                    "firstname" => $input['firstname'],
                    "lastname" => $input['lastname'],
                    "email" => $input['email'],
                    'secondary_email' => array_key_exists('secondary_email', $input) ? $input['secondary_email'] : NULL,
                    'preferred_email' => array_key_exists('preferred_email', $input) ? $input['preferred_email'] : 'primary',
                    'provider' =>  Config::get('constants.provider.service'),
                    'sector_id' => $input['sector_id'],
                    'organisation' => $input['organisation'],
                    'bio' => $input['bio'],
                    'domain' => $input['domain'],
                    'link' => $input['link'],
                    'orcid' => $input['orcid'],
                    'contact_feedback' => $input['contact_feedback'],
                    'contact_news' => $input['contact_news'],  
                    'mongo_id' => $input['mongo_id'], 
                    'mongo_object_id' => $input['mongo_object_id'],
                    'terms' => array_key_exists('terms', $input) ? $input['terms'] : 0,                
                ];

                $arrayUserNotification = array_key_exists('notifications', $input) ? $input['notifications'] : [];

                UserHasNotification::where('user_id', $id)->delete();
                foreach ($arrayUserNotification as $value) {
                    UserHasNotification::updateOrCreate([
                        'user_id' => (int) $id,
                        'notification_id' => (int) $value,
                    ]);
                }

                $user->update($array);

                $this->updateOrCreateContact($id);

                Auditor::log([
                    'user_id' => (int) $jwtUser['id'],
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@'.__FUNCTION__,
                    'description' => "User " . $id . " updated",
                ]);

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
     * @OA\Patch(
     *    path="/api/v1/users/{id}",
     *    operationId="edit_users",
     *    tags={"Users"},
     *    summary="UserController@edit",
     *    description="Edit user",
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
    public function edit(EditUser $request, int $id): mixed
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $arrayKeys = [
                'firstname',
                'lastname',
                'email',
                'secondary_email',
                'preferred_email',
                'provider',
                'sector_id',
                'organisation',
                'bio',
                'domain',
                'link',
                'orcid',
                'contact_feedback',
                'contact_news',
                'mongo_id',
                'mongo_object_id', 
                'terms',                
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            if (array_key_exists('firstname', $input) && array_key_exists('lastname', $input)) {
                $array['name'] = $input['firstname'] . " " . $input['lastname'];
            }

            if (array_key_exists('password', $input)) {
                $array['password'] = Hash::make($input['password']);
            }

            User::withTrashed()->where('id', $id)->update($array);

            $arrayUserNotification = array_key_exists('notifications', $input) ? $input['notifications'] : [];
                
            UserHasNotification::where('user_id', $id)->delete();
            foreach ($arrayUserNotification as $value) {
                UserHasNotification::updateOrCreate([
                    'user_id' => (int) $id,
                    'notification_id' => (int) $value,
                ]);
            }

            $this->updateOrCreateContact($id);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "User " . $id . " updated",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => User::withTrashed()->where('id', $id)->with(['notifications'])->first(),
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
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            UserHasNotification::where('user_id', $id)->delete();
            UserHasRole::where('user_id', $id)->delete();
            User::where('id', $id)->delete();

            $this->updateOrCreateContact($id);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "User " . $id . " deleted",
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
