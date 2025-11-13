<?php

namespace App\Http\Controllers\Api\V1;

use Hash;
use Config;
use Auditor;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
use App\Models\EmailVerification;
use Carbon\Carbon;
use App\Models\EmailTemplate;
use App\Jobs\SendEmailJob;

class UserController extends Controller
{
    use UserTransformation;
    use RequestTransformation;
    use HubspotContacts;

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
     *          @OA\Property(property="current_page", type="integer", example="1"),
     *             @OA\Property(property="data", type="array", example="[]",
     *                @OA\Items(type="array",@OA\Items()),
     *             ),
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/users?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/users?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/users"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(IndexUser $request): mixed
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $perPage = request('per_page', Config::get('constants.per_page'));
        $mini = $request->has('mini');
        $users = [];

        try {
            if (count($jwtUser)) {
                $userIsAdmin = (bool)$jwtUser['is_admin'];

                if ($request->filled('filterNames')) {
                    $chars = strtolower($request->query('filterNames'));
                    $users = [
                        "data" => User::where(DB::raw('LOWER(name)'), 'like', '%' . $chars . '%')
                                    ->select('id', 'name', 'email')
                                    ->get()
                                    ->map(function ($user) {
                                        $user->email = $this->maskEmail($user->email);
                                        return $user;
                                    })
                    ];
                } elseif ($mini) {
                    //temporary force to get all users but with masked email
                    // - will not be needed in the future as can just use the above if block
                    $users = DB::table('users')
                        ->select('id', 'name', 'email')
                        ->orderBy('name')
                        ->get()
                        ->map(function ($user) {
                            $user->email = $this->maskEmail($user->email);
                            return $user;
                        });

                    $users = ['data' => $users];
                } elseif ($userIsAdmin) {
                    $users = User::with(['roles', 'roles.permissions', 'teams', 'notifications'])->paginate($perPage, ['*'], 'page');
                } else {
                    $users = User::select('id', 'name')->paginate($perPage, ['*'], 'page');
                }

            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User get all',
            ]);

            return response()->json($users, Config::get('statuscodes.STATUS_OK.code'));
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
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
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'GET',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'User get ' . $id,
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $array = [
                'name' => $input['firstname'] . " " . $input['lastname'],
                'firstname' => $input['firstname'],
                'lastname' => $input['lastname'],
                'email' => $input['email'],
                'secondary_email' => array_key_exists('secondary_email', $input) ? $input['secondary_email'] : null,
                'preferred_email' => array_key_exists('preferred_email', $input) ? $input['preferred_email'] : 'primary',
                'provider' =>  array_key_exists('provider', $input) ? $input['provider'] : Config::get('constants.provider.service'),
                'providerid' => array_key_exists('providerid', $input) ? $input['providerid'] : null,
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
                'is_nhse_sde_approval' => array_key_exists('is_nhse_sde_approval', $input) ? $input['is_nhse_sde_approval'] : 0,
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
                        'user_id' => (int)$user->id,
                        'notification_id' => (int)$value,
                    ]);
                }
            } else {
                throw new NotFoundException();
            }

            $this->updateOrCreateContact($user->id);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User ' . $user->id . ' created',
            ]);

            return response()->json([
                'message' => 'created',
                'data' => $user->id,
            ], 201);
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $user = User::findOrFail($id);
            if ($user) {
                $array = [
                    'name' => $input['firstname'] . " " . $input['lastname'],
                    'firstname' => $input['firstname'],
                    'lastname' => $input['lastname'],
                    'email' => $input['email'],
                    'provider' => array_key_exists('provider', $input) ? $input['provider'] : $user->provider,
                    'providerid' => array_key_exists('providerid', $input) ? $input['providerid'] : $user->providerid,
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
                    'is_nhse_sde_approval' => array_key_exists('is_nhse_sde_approval', $input) ? $input['is_nhse_sde_approval'] : 0,
                ];

                if (array_key_exists('secondary_email', $input)) {
                    if ($input['secondary_email'] !== $user->secondary_email) {
                        // below if a user already has a secondary verified email and is changing
                        $array['secondary_email_verified_at'] = null;

                        EmailVerification::where('user_id', $user->id)->update(['expires_at' => now()]);

                        $newToken = Str::uuid();

                        $verification = EmailVerification::create([
                            'uid' => $newToken,
                            'user_id' => $user->id,
                            'is_secondary' => true,
                            'expires_at' => Carbon::now()->addHours(24),
                        ]);

                        $template = EmailTemplate::where('identifier', '=', 'user.email_verification')->first();

                        $replacements = [
                            '[[UUID]]' => $newToken,
                            '[[USER_FIRST_NAME]]' => $input['firstname'] ?? $user->firstname,
                        ];

                        if ($template && !empty($input['secondary_email'])) {
                            $to = [
                            'to' => [
                              'email' => $input['secondary_email'],
                              'name' => $user['name'],
                            ],
                                  ];
                            SendEmailJob::dispatch($to, $template, $replacements);
                        }
                    }






                    if ($user->provider === 'open-athens') {
                        // If the user has a secondary email, use it; otherwise, use the input value.
                        $array['secondary_email'] = is_null($user->secondary_email) ? $input['secondary_email'] : $user->secondary_email;
                    } else {
                        // For all other providers, use the input value.
                        $array['secondary_email'] = $input['secondary_email'];
                    }
                }

                if (array_key_exists('preferred_email', $input)) {
                    $array['preferred_email'] = $input['preferred_email'];
                }

                $arrayUserNotification = array_key_exists('notifications', $input) ?
                    $input['notifications'] : [];

                UserHasNotification::where('user_id', $id)->delete();
                foreach ($arrayUserNotification as $value) {
                    UserHasNotification::updateOrCreate([
                        'user_id' => (int)$id,
                        'notification_id' => (int) $value,
                    ]);
                }

                $user->update($array);

                $this->updateOrCreateContact($id);

                Auditor::log([
                    'user_id' => (int)$jwtUser['id'],
                    'action_type' => 'UPDATE',
                    'action_name' => class_basename($this) . '@' . __FUNCTION__,
                    'description' => 'User ' . $id . ' updated',
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $user = User::findOrFail($id);
            $arrayKeys = [
                'firstname',
                'lastname',
                'email',
                'secondary_email',
                'preferred_email',
                'provider',
                'providerid',
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
                'is_nhse_sde_approval',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            if (array_key_exists('secondary_email', $array)) {
                $array['secondary_email'] = $user->provider === 'open-athens' ? $user->secondary_email : $array['secondary_email'];
            }

            if (array_key_exists('preferred_email', $array)) {
                $array['preferred_email'] = $user->provider === 'open-athens' ? $user->preferred_email : $input['preferred_email'];
            }

            if (array_key_exists('firstname', $input) && array_key_exists('lastname', $input)) {
                $array['name'] = $input['firstname'] . ' ' . $input['lastname'];
            }

            if (array_key_exists('password', $input)) {
                $array['password'] = Hash::make($input['password']);
            }

            User::withTrashed()->where('id', $id)->update($array);

            $arrayUserNotification = array_key_exists('notifications', $input) ? $input['notifications'] : [];

            UserHasNotification::where('user_id', $id)->delete();
            foreach ($arrayUserNotification as $value) {
                UserHasNotification::updateOrCreate([
                    'user_id' => (int)$id,
                    'notification_id' => (int)$value,
                ]);
            }

            $this->updateOrCreateContact($id);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => User::withTrashed()->where('id', $id)->with(['notifications'])->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
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
     * @OA\Get(
     *    path="/api/v1/users/verify-secondary-email/{uuid}",
     *    operationId="verify_secondary_email",
     *    tags={"Users"},
     *    summary="Verify user's secondary email using a UUID",
     *    description="This endpoint verifies the secondary email for a user if the UUID is valid and not expired.",
     *    @OA\Parameter(
     *       name="uuid",
     *       in="path",
     *       description="Verification UUID",
     *       required=true,
     *       @OA\Schema(
     *          type="string",
     *          example="03af1f5e-5cd2-4c41-ae23-56dd2c9efc67"
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Email verified successfully",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Secondary email verified successfully."),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Invalid or expired token",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Verification link is invalid or has expired."),
     *       ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="UUID not found",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Verification token not found."),
     *       ),
     *    )
     * )
     */
    public function verifySecondaryEmail(string $uuid)
    {
        $token = EmailVerification::where('uid', $uuid)
            ->where('is_secondary', true)
            ->first();

        if (!$token) {
            return response()->json([
                    'message' => 'Verification token not valid.',
                ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }

        if (Carbon::now()->greaterThan($token->expires_at)) {
            return response()->json([
                'message' => 'Verification link has expired.'
            ], Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
        }

        $user = User::find($token->user_id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        }

        $user->update([
            'secondary_email_verified_at' => now(),
        ]);

        // bin off token after use
        $token->delete();

        return response()->json([
            'message' => 'Secondary email verified successfully.',
        ]);
    }
    /**
 * @OA\Post(
 *     path="/api/v1/users/{id}/resend-secondary-verification",
 *     operationId="resendSecondaryVerificationEmail",
 *     tags={"Users"},
 *     summary="Resend secondary email verification",
 *     description="Resends the verification email for the secondary email address. Old tokens are expired.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="User ID",
 *         @OA\Schema(type="integer", example=123)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Verification email resent",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Verification email resent.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User or secondary email not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Secondary email not found")
 *         )
 *     )
 * )
 */
    public function resendSecondaryVerificationEmail(int $id)
    {
        $user = User::find($id);

        if (!$user || !$user->secondary_email) {
            return response()->json(['message' => 'Secondary email not found'], 404);
        }

        // Expire all existing tokens for this user
        DB::table('email_verifications')
            ->where('user_id', $user->id)
            ->where('is_secondary', true)
            ->delete();

        $newToken = Str::uuid();

        // Create new verification token
        EmailVerification::create([
            'uid' => $newToken,
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addHours(24),
            'is_secondary' => true,
        ]);

        // Get email template
        $template = EmailTemplate::where('identifier', '=', 'user.email_verification')->first();

        $replacements = [
            '[[UUID]]' => $newToken,
            '[[USER_FIRST_NAME]]' => $user->firstname,
        ];

        if ($template) {
            $to = [
                'to' => [
                    'email' => $user->secondary_email,
                    'name' => $user->name,
                ],
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        }

        return response()->json([
            'message' => 'Verification email resent.',
        ]);
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            UserHasNotification::where('user_id', $id)->delete();
            UserHasRole::where('user_id', $id)->delete();
            User::where('id', $id)->delete();

            $this->updateOrCreateContact($id);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'User ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
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
}
