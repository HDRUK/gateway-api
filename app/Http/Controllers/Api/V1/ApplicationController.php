<?php

namespace App\Http\Controllers\Api\V1;

use Hash;
use Config;
use Auditor;
use Exception;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\Application;
use App\Models\TeamHasUser;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\TeamUserHasRole;
use App\Http\Traits\CheckAccess;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\ApplicationHasPermission;
use App\Http\Traits\RequestTransformation;
use App\Models\ApplicationHasNotification;
use App\Http\Requests\Application\GetApplication;
use App\Http\Requests\Application\EditApplication;
use App\Http\Requests\Application\CreateApplication;
use App\Http\Requests\Application\DeleteApplication;
use App\Http\Requests\Application\UpdateApplication;
use App\Http\Requests\Application\GenerateApplication;

class ApplicationController extends Controller
{
    use RequestTransformation;
    use CheckAccess;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/applications",
     *    operationId="fetch_all_applications",
     *    tags={"Application"},
     *    summary="ApplicationController@index",
     *    description="Returns a list of applications",
     *    @OA\Parameter(
     *       name="teamId",
     *       in="query",
     *       description="Filter Apps by the teamId",
     *       @OA\Schema(type="integer")
     *    ),
     *    @OA\Parameter(
     *       name="text",
     *       in="query",
     *       description="Search term to filter by application name or description.",
     *       @OA\Schema(type="string")
     *    ),
     *    @OA\Parameter(
     *       name="status",
     *       in="query",
     *       description="Filter by application status is enabled or not (true or false).",
     *       @OA\Schema(type="string", enum={"1", "0"})
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Success",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="Voluptas incidunt repellat animi. Sed ut beatae fugit ullam."),
     *                   @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ3"),
     *                   @OA\Property(property="client_id", type="string", example="w8CLyeP8vhnPK1V0mJ8ubU7UHCVnK7Bm"),
     *                   @OA\Property(property="image_link", type="string", example="hhttps:\/\/via.placeholder.com\/640x480.png\/0044ee?text=animals+harum"),
     *                   @OA\Property(property="description", type="string", example="Magni minima facilis quo soluta. Ab quasi quaerat doloremque. Sapiente asperiores nisi maiores ex quia velit."),
     *                   @OA\Property(property="team_id", type="integer", example="1"),
     *                   @OA\Property(property="user_id", type="integer", example="2"),
     *                   @OA\Property(property="status", type="boolean", example="false"),
     *                   @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                )
     *             )
     *          )
     *       )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $teamId = $request->query('team_id', null);
        if (!is_null($teamId)) {
            $this->checkAccess($input, $teamId, null, 'team');
        }
        $apps = Application::where('user_id', $jwtUser)->whereNotNull('team_id')->select('team_id')->distinct()->get();
        foreach ($apps as $app) {
            $this->checkAccess($input, $app->team_id, null, 'team');
        }

        try {
            $applications = Application::getAll('user_id', $jwtUser)->with(['permissions','team','user','notifications.userNotification']);

            if (!is_null($teamId)) {
                $applications = $applications->where('team_id', (int)$teamId);
            }

            if ($request->has('status')) {
                $applicationStatus = $request->query('status');
                if ($applicationStatus === "1" || $applicationStatus === "0") {
                    $applications = $applications->where('enabled', (int) $applicationStatus);
                }
            }

            $textTerms = $request->query('text', []);
            if ($textTerms !== null) {
                if (!is_array($textTerms)) {
                    $textTerms = [$textTerms];
                }
                foreach ($textTerms as $textTerm) {
                    $applications = $applications->where(function ($query) use ($textTerm) {
                        $query->where('name', 'like', '%' . $textTerm . '%')
                              ->orWhere('description', 'like', '%' . $textTerm . '%');
                    });
                }
            }

            $perPage = request('per_page', Config::get('constants.per_page'));
            $applications = $applications->paginate($perPage, ['*'], 'page');

            $applications->getCollection()->each(function ($application) {
                $application->makeHidden(['client_secret']);
            });

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Application get all',
            ]);

            return response()->json(
                $applications
            );
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/applications/{id}",
     *    operationId="fetch_applications",
     *    tags={"Application"},
     *    summary="ApplicationController@show",
     *    description="Get application by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="name", type="string", example="Voluptas incidunt repellat animi. Sed ut beatae fugit ullam."),
     *                   @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ3"),
     *                   @OA\Property(property="client_id", type="string", example="w8CLyeP8vhnPK1V0mJ8ubU7UHCVnK7Bm"),
     *                   @OA\Property(property="image_link", type="string", example="hhttps:\/\/via.placeholder.com\/640x480.png\/0044ee?text=animals+harum"),
     *                   @OA\Property(property="description", type="string", example="Magni minima facilis quo soluta. Ab quasi quaerat doloremque. Sapiente asperiores nisi maiores ex quia velit."),
     *                   @OA\Property(property="team_id", type="integer", example="1"),
     *                   @OA\Property(property="user_id", type="integer", example="2"),
     *                   @OA\Property(property="enabled", type="boolean", example="false"),
     *                   @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="team", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                ),
     *             ),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function show(GetApplication $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $application = Application::with(['permissions','team','user','notifications.userNotification'])->where('id', $id)->first();
        $this->checkAccess($input, $application->team_id, null, 'team');

        try {
            $application->makeHidden(['client_secret']);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => "Application get " . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $application,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/applications",
     *    summary="Create application",
     *    description="Creates application",
     *    tags={"Application"},
     *    summary="ApplicationController@store",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *        required=true,
     *        description="Application definition",
     *        @OA\JsonContent(
     *            @OA\Property(property="name", type="string", example="Corrupti in a voluptas. Eligendi saepe sed sit."),
     *            @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam"),
     *            @OA\Property(property="description", type="string", example="Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. "),
     *            @OA\Property(property="team_id", type="integer", example="1"),
     *            @OA\Property(property="user_id", type="integer", example="2"),
     *            @OA\Property(property="enabled", type="boolean", example="false"),
     *            @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *            @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *           @OA\Property(property="data", type="array",
     *              @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example="123"),
     *                 @OA\Property(property="name", type="string", example="Voluptas incidunt repellat animi. Sed ut beatae fugit ullam."),
     *                 @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ3"),
     *                 @OA\Property(property="client_id", type="string", example="w8CLyeP8vhnPK1V0mJ8ubU7UHCVnK7Bm"),
     *                 @OA\Property(property="client_secret", type="string", example="w8CLyeP8vhnPK1V0mJ8ubU7UHCVnK7Bm"),
     *                 @OA\Property(property="image_link", type="string", example="hhttps:\/\/via.placeholder.com\/640x480.png\/0044ee?text=animals+harum"),
     *                 @OA\Property(property="description", type="string", example="Magni minima facilis quo soluta. Ab quasi quaerat doloremque. Sapiente asperiores nisi maiores ex quia velit."),
     *                 @OA\Property(property="team_id", type="integer", example="1"),
     *                 @OA\Property(property="user_id", type="integer", example="2"),
     *                 @OA\Property(property="enabled", type="boolean", example="false"),
     *                 @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *                 @OA\Property(property="team", type="array", example="[]", @OA\Items()),
     *                 @OA\Property(property="user", type="array", example="[]", @OA\Items()),
     *                 @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *              ),
     *           ),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error")
     *        )
     *    )
     * )
     */
    public function store(CreateApplication $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $this->checkAccess($input, $input['team_id'], null, 'team');

        try {
            // While it seems weak, random uses openssl_random_pseudo_bytes under the hood
            // which is cryptographically secure. Increasing the length of the string
            // returned, increases security further still
            $appId = Str::random(40);
            $clientId = Str::random(40);
            $clientSecret = Hash::make(
                $appId .
                ':' . $clientId .
                ':' . env('APP_AUTH_PRIVATE_SALT') .
                ':' . env('APP_AUTH_PRIVATE_SALT_2')
            );

            $array = [
                'name' => $input['name'],
                'app_id' => $appId,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'description' => $input['description'],
                'team_id' => $input['team_id'],
                'user_id' => $input['user_id'],
                'enabled' => $input['enabled'],
            ];

            if (array_key_exists('image_link', $input)) {
                $array['image_link'] = $input['image_link'];
            }

            $application = Application::create($array);

            if (array_key_exists('permissions', $input)) {
                $this->applicationHasPermissions((int) $application->id, $input['permissions']);
            }

            if (array_key_exists('notifications', $input)) {
                $this->applicationHasNotifications((int) $application->id, $input['notifications']);
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_user_id' => $input['user_id'],
                'target_team_id' => $input['team_id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Application ' . $application->id . ' created',
            ]);

            $this->sendEmail((int)$application->id, 'CREATE');

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => Application::with(['permissions', 'team', 'user'])
                    ->where('id', $application->id)
                    ->first(),
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_user_id' => $input['user_id'],
                'target_team_id' => $input['team_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/applications/{id}",
     *    tags={"Application"},
     *    summary="Update application",
     *    description="Update application",
     *    summary="ApplicationController@update",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *        required=true,
     *        description="ActivityLog definition",
     *        @OA\JsonContent(
     *            required={"name", "app_id", "client_id", "image_link", "description", "team_id", "user_id", "enabled", "tags", "permissions"},
     *            @OA\Property(property="name", type="string", example="Corrupti in a voluptas. Eligendi saepe sed sit."),
     *            @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam"),
     *            @OA\Property(property="description", type="string", example="Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. "),
     *            @OA\Property(property="team_id", type="integer", example="1"),
     *            @OA\Property(property="user_id", type="integer", example="2"),
     *            @OA\Property(property="enabled", type="boolean", example="false"),
     *            @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *            @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *        ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="covid"),
     *                 @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ4"),
     *                 @OA\Property(property="client_id", type="string", example="iem4i3geb1FxehvvQBlSOZ2A6S6digs"),
     *                 @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *                 @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *                 @OA\Property(property="enabled", type="boolean", example="true"),
     *                 @OA\Property(property="public", type="boolean", example="true"),
     *                 @OA\Property(property="counter", type="integer", example="123"),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *              ),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error")
     *        )
     *    )
     * )
     */
    public function update(UpdateApplication $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initApplication = Application::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initApplication->team_id, null, 'team');

        try {
            $array = [
                'name' => $input['name'],
                'description' => $input['description'],
                'team_id' => $input['team_id'],
                'user_id' => $input['user_id'],
                'enabled' => $input['enabled'],
            ];

            if (array_key_exists('image_link', $input)) {
                $array['image_link'] = $input['image_link'];
            }

            Application::where('id', $id)->update($array);

            if (array_key_exists('permissions', $input)) {
                $this->applicationHasPermissions((int) $id, $input['permissions']);
            }

            if (array_key_exists('notifications', $input)) {
                $this->applicationHasNotifications((int) $id, $input['notifications']);
            }

            $application = Application::with(['permissions','team','user','notifications'])->where('id', $id)->first();
            $application->makeHidden(['client_secret']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_user_id' => $input['user_id'],
                'target_team_id' => $input['team_id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Application ' . $id . ' updated',
            ]);

            $this->sendEmail((int)$id, 'UPDATE');

            return response()->json([
                'message' => 'success',
                'data' => $application
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_user_id' => $input['user_id'],
                'target_team_id' => $input['team_id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/applications/{id}",
     *    tags={"Application"},
     *    summary="Edit application",
     *    description="Edit application",
     *    summary="ApplicationController@edit",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *        required=true,
     *        description="ActivityLog definition",
     *        @OA\JsonContent(
     *            @OA\Property(property="name", type="string", example="Corrupti in a voluptas. Eligendi saepe sed sit."),
     *            @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ4"),
     *            @OA\Property(property="client_id", type="string", example="iem4i3geb1FxehvvQBlSOZ2A6S6digs"),
     *            @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam"),
     *            @OA\Property(property="description", type="string", example="Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. "),
     *            @OA\Property(property="team_id", type="integer", example="1"),
     *            @OA\Property(property="user_id", type="integer", example="2"),
     *            @OA\Property(property="enabled", type="boolean", example="false"),
     *            @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *            @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *        ),
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Not found response",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="not found")
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="covid"),
     *                 @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ4"),
     *                 @OA\Property(property="client_id", type="string", example="iem4i3geb1FxehvvQBlSOZ2A6S6digs"),
     *                 @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *                 @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *                 @OA\Property(property="enabled", type="boolean", example="true"),
     *                 @OA\Property(property="public", type="boolean", example="true"),
     *                 @OA\Property(property="counter", type="integer", example="123"),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *              ),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error")
     *        )
     *    )
     * )
     */
    public function edit(EditApplication $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initApplication = Application::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initApplication->team_id, null, 'team');

        try {
            $arrayKeys = ['name', 'image_link', 'description', 'team_id', 'user_id', 'enabled'];
            $array = $this->checkEditArray($input, $arrayKeys);

            Application::where('id', $id)->update($array);

            if (array_key_exists('permissions', $input)) {
                $this->applicationHasPermissions((int) $id, $input['permissions']);
            }

            if (array_key_exists('notifications', $input)) {
                $this->applicationHasNotifications((int) $id, $input['notifications']);
            }

            $application = Application::with(['permissions','team','user','notifications'])->where('id', $id)->first();
            $application->makeHidden(['client_secret']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_user_id' => $application['user_id'],
                'target_team_id' => $application['team_id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Application ' . $id . ' updated',
            ]);

            $this->sendEmail((int)$id, 'UPDATE');

            return response()->json([
                'message' => 'success',
                'data' => $application
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/applications/{id}",
     *    tags={"Application"},
     *    summary="Delete application",
     *    description="Delete application",
     *    summary="ApplicationController@delete",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
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
    public function destroy(DeleteApplication $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initApplication = Application::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initApplication->team_id, null, 'team');

        try {
            $this->sendEmail((int)$id, 'DELETE');

            Application::where('id', $id)->delete();
            ApplicationHasPermission::where('application_id', $id)->delete();

            // I don't know if deleting an application should also delete the connection with the notifications.
            // What happens if undo is done after delete?
            $applicationHasNotificationIds = ApplicationHasNotification::where('application_id', $id)->pluck('notification_id');

            foreach ($applicationHasNotificationIds as $applicationHasNotificationId) {
                Notification::where('id', $applicationHasNotificationId)->delete();
                ApplicationHasNotification::where('notification_id', $applicationHasNotificationId)->delete();
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Application ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => 'success',
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/applications/{id}/clientid",
     *    tags={"Application"},
     *    summary="Generate Client ID application",
     *    description="Generate Client ID application",
     *    summary="ApplicationController@generateClientIdById",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="application id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="application id",
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
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="covid"),
     *                 @OA\Property(property="app_id", type="string", example="obmWCcsccdxH5iHgLTJDZNXNkyW1ZxZ4"),
     *                 @OA\Property(property="client_id", type="string", example="iem4i3geb1FxehvvQBlSOZ2A6S6digs"),
     *                 @OA\Property(property="description", type="string", example="Dolorem voluptas consequatur nihil illum et sunt libero."),
     *                 @OA\Property(property="image_link", type="string", example="https://via.placeholder.com/640x480.png/0022bb?text=animals+cumque"),
     *                 @OA\Property(property="enabled", type="boolean", example="true"),
     *                 @OA\Property(property="public", type="boolean", example="true"),
     *                 @OA\Property(property="counter", type="integer", example="123"),
     *                 @OA\Property(property="created_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="updated_at", type="datetime", example="2023-04-11 12:00:00"),
     *                 @OA\Property(property="deleted_at", type="datetime", example="2023-04-11 12:00:00"),
     *              ),
     *        ),
     *    ),
     *    @OA\Response(
     *        response=500,
     *        description="Error",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="error")
     *        )
     *    )
     * )
     */
    public function generateClientIdById(GenerateApplication $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
        $initApplication = Application::withTrashed()->where('id', $id)->first();
        $this->checkAccess($input, $initApplication->team_id, null, 'team');

        try {
            $clientId = Str::random(40);
            $clientSecret = Hash::make(
                $initApplication->app_id .
                ':' . $clientId .
                ':' . env('APP_AUTH_PRIVATE_SALT') .
                ':' . env('APP_AUTH_PRIVATE_SALT_2')
            );

            $array = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ];

            Application::where(['id' => $id])->update($array);

            $application = Application::with(['permissions','team','user','notifications.userNotification'])->where('id', $id)->first();
            $application->makeHidden(['client_secret']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'target_user_id' => $application['user_id'],
                'target_team_id' => $application['team_id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Application ' . $id . ' updated',
            ]);

            $this->sendEmail((int)$id, 'UPDATE.CLIENTID');

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $application,
            ], Config::get('statuscodes.STATUS_OK.code'));

        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Application has permissions associated
     *
     * @param integer $applicationId
     * @param array $permissions
     * @return mixed
     */
    private function applicationHasPermissions(int $applicationId, array $permissions): mixed
    {
        try {
            ApplicationHasPermission::where('application_id', $applicationId)->delete();
            foreach ($permissions as $permission) {
                ApplicationHasPermission::create([
                    'application_id' => $applicationId,
                    'permission_id' => $permission,
                ]);
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Application has notifications associated
     *
     * @param integer $applicationId
     * @param array $notifications
     * @return mixed
     */
    private function applicationHasNotifications(int $applicationId, array $notifications): mixed
    {
        try {
            $applicationHasNotificationIds = ApplicationHasNotification::where('application_id', $applicationId)->pluck('notification_id');

            foreach ($applicationHasNotificationIds as $applicationHasNotificationId) {
                Notification::where('id', $applicationHasNotificationId)->delete();
                ApplicationHasNotification::where('notification_id', $applicationHasNotificationId)->delete();
            }

            foreach ($notifications as $notification) {
                // $notification may be a user id, or it may be an email address.
                $notification = Notification::create([
                    'notification_type' => 'application',
                    'message' => '',
                    'opt_in' => 0,
                    'enabled' => 1,
                    'email' => is_numeric($notification) ? null : $notification,
                    'user_id' => is_numeric($notification) ? (int) $notification : null,
                ]);

                ApplicationHasNotification::create([
                    'application_id' => $applicationId,
                    'notification_id' => $notification->id,
                ]);
            }

            return true;
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    // send email
    public function sendEmail(int $appId, string $type)
    {
        $app = Application::with(['team','user','notifications.userNotification'])->where('id', $appId)->withTrashed()->first();
        if (is_null($app)) {
            throw new Exception('Application not found!');
        }

        $template = null;
        switch ($type) {
            case 'CREATE':
                $template = EmailTemplate::where('identifier', 'private.app.create')->first();
                break;
            case 'UPDATE':
                $template = EmailTemplate::where('identifier', 'private.app.update')->first();
                break;
            case 'DELETE':
                $template = EmailTemplate::where('identifier', 'private.app.delete')->first();
                break;
            case 'UPDATE.CLIENTID':
                $template = EmailTemplate::where('identifier', 'private.app.update.clientid')->first();
                break;
            default:
                throw new Exception("Send email type not found!");
                break;
        }

        $receivers = $this->sendEmailTo($app);

        $listOfAdminDevNames = convertArrayToHtmlUlList($receivers['user_admin_dev_name']);
        $textAdminDev = 'To review or edit the integration, including generating a new <b>Client ID</b>, click the link below or visit your account on the Gateway.';
        $textNoAdminDev = 'To review or edit the integration, contact your Team Administrator(s) or Developer(s):<br>' . $listOfAdminDevNames;

        $listOfPermsions = $this->listOfAppPermissions($appId);

        foreach ($receivers['users'] as $receiver) {
            $to = [
                'to' => [
                    'email' => $receiver['email'],
                    'name' => $receiver['name'],
                ],
            ];

            $replacements = [
                '[[TEAM_ID]]' => $app->team->id,
                '[[TEAM_NAME]]' => $app->team->name,
                '[[USER_FIRSTNAME]]' => $receiver['firstname'],
                '[[APP_NAME]]' => $app->name,
                '[[APP_CREATED_AT_DATE]]' => $app->created_at,
                '[[APP_UPDATED_AT_DATE]]' => $app->updated_at,
                '[[APP_DELETED_AT_DATE]]' => date('Y-m-d'),
                '[[APP_STATUS]]' => $app->enabled ? 'enabled' : 'disabled',
                '[[APP_PERMISSIONS_LIST]]' => $listOfPermsions,
                '[[OTHER_USER_MESSAGE]]' => in_array($receiver['id'], $receivers['user_admin_dev_ids']) ? $textAdminDev : $textNoAdminDev,
                '[[CURRENT_YEAR]]' => date('Y'),
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        }

    }

    public function sendEmailTo(Application $app): array
    {
        $teamId = $app->team_id;
        $userId = $app->user_id;
        // only for users with the following roles: 'custodian.team.admin', 'developer'
        $roles = Role::whereIn('name', ['custodian.team.admin', 'developer'])->select('id')->get();
        $roles = convertArrayToArrayWithKeyName($roles, 'id');
        $teamHasUsers = TeamHasUser::where('team_id', $teamId)->select('id', 'user_id')->get();

        $notificationuserId = [];
        $userAdminDevNames = [];
        $userAdminDevIds = [];
        foreach ($teamHasUsers as $item) {
            $teamUserHasRoles = TeamUserHasRole::whereIn('role_id', $roles)->where('team_has_user_id', $item->id)->first();
            if (!is_null($teamUserHasRoles)) {
                $notificationuserId[] = $item->user_id;
            }

            $user = User::where(['id' => $item->user_id])->select(['name'])->first();
            if (!is_null($user)) {
                $userAdminDevNames[] = $user->name;
                $userAdminDevIds[] = $item->user_id;
            }
        }


        $notificationuserId[] = $userId;
        $notificationuserId = array_unique($notificationuserId);
        $users = User::whereIn('id', $notificationuserId)->select(['id', 'firstname', 'name', 'email'])->get()->toArray();

        return [
            'user_admin_dev_ids' => $userAdminDevIds,
            'user_admin_dev_name' => $userAdminDevNames,
            'users' => $users,
        ];
    }

    public function listOfAppPermissions(int $appId)
    {
        $applications = Application::with(['permissions'])->where(['id' => $appId])->first()->toArray();
        $permissions = [];
        foreach ($applications['permissions'] as $item) {
            $permissions[] = $item['name'];
        }

        return convertArrayToHtmlUlList($permissions);
    }

}
