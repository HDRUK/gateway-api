<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use App\Models\Role;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\Federation;
use App\Models\TeamHasUser;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\TeamUserHasRole;
use App\Models\TeamHasFederation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\FederationHasNotification;
// use App\Http\Traits\LoggingContext;
use App\Http\Traits\RequestTransformation;
use App\Http\Requests\Federation\GetFederation;
use App\Http\Requests\Federation\EditFederation;
use App\Http\Requests\Federation\CreateFederation;
use App\Http\Requests\Federation\DeleteFederation;
use App\Http\Requests\Federation\GetAllFederation;
use App\Http\Requests\Federation\UpdateFederation;

class FederationController extends Controller
{
    use RequestTransformation;
    // use LoggingContext;

    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/federations",
     *    operationId="get_federation_team_id",
     *    tags={"Team-Federations"},
     *    summary="FederationController@index",
     *    description="Get federations by team id",
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
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="current_page", type="integer", example="1"),
     *             @OA\Property(property="data", type="array",
     *                @OA\Items(type="object",
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="federation_type", type="string", example="federation-type-eqhbjcnl"),
     *                   @OA\Property(property="auth_type", type="string", example="api_key"),
     *                   @OA\Property(property="auth_secret_key", type="string", example="velit sapiente"),
     *                   @OA\Property(property="endpoint_baseurl", type="string", example="https:\/\/www.ortiz.com\/enim-recusandae-aspernatur-quidem-cum-delectus-adipisci"),
     *                   @OA\Property(property="endpoint_datasets", type="string", example="\/sed-aut-corrupti-quas-adipisci-aliquam-ad"),
     *                   @OA\Property(property="endpoint_dataset", type="string", example="\/sed-aut-corrupti-quas-adipisci-aliquam-ad\/{id}"),
     *                   @OA\Property(property="run_time_hour", type="integer", example="5"),
     *                   @OA\Property(property="run_time_minute", type="string", example="00"),
     *                   @OA\Property(property="enabled", type="boolean", example="1"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="tested", type="boolean", example="0"),
     *                   @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *                ),
     *             ),
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams\/19\/federations?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams\/19\/federations?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams\/19\/federations"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(GetAllFederation $request, int $teamId)
    {
        // $loggingContext = $this->getLoggingContext($request);
        // $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $perPage = request('per_page', Config::get('constants.per_page'));
            $federations = Federation::whereHas('team', function ($query) use ($teamId) {
                $query->where('id', $teamId);
            })->with(['team', 'notifications.userNotification'])->paginate($perPage, ['*'], 'page');

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation get all',
            ]);

            return response()->json(
                $federations
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            //\Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/federations/{federationId}",
     *    operationId="get_federation_by_federation_id_and_team_id",
     *    tags={"Team-Federations"},
     *    summary="FederationController@show",
     *    description="Get federation by federation id from team id",
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
     *       name="federationId",
     *       in="path",
     *       description="federation id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="federation id",
     *       ),
     *    ),
     *    @OA\Response(
     *        response=200,
     *        description="Success",
     *        @OA\JsonContent(
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="data", type="object",
     *              @OA\Property(property="id", type="integer", example="123"),
     *              @OA\Property(property="federation_type", type="string", example="federation-type-eqhbjcnl"),
     *              @OA\Property(property="auth_type", type="string", example="api_key"),
     *              @OA\Property(property="auth_secret_key", type="string", example="velit sapiente"),
     *              @OA\Property(property="endpoint_baseurl", type="string", example="https:\/\/www.ortiz.com\/enim-recusandae-aspernatur-quidem-cum-delectus-adipisci"),
     *              @OA\Property(property="endpoint_datasets", type="string", example="\/sed-aut-corrupti-quas-adipisci-aliquam-ad"),
     *              @OA\Property(property="endpoint_dataset", type="string", example="\/sed-aut-corrupti-quas-adipisci-aliquam-ad\/{id}"),
     *              @OA\Property(property="run_time_hour", type="integer", example="5"),
     *              @OA\Property(property="run_time_minute", type="string", example="00"),
     *              @OA\Property(property="enabled", type="boolean", example="1"),
     *              @OA\Property(property="counter", type="integer", example="34319"),
     *              @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="tested", type="boolean", example="0"),
     *              @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *           ),
     *        ),
     *    ),
     * )
     */
    public function show(GetFederation $request, int $teamId, int $federationId)
    {
        // $loggingContext = $this->getLoggingContext($request);
        // $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $federations = Federation::whereHas('team', function ($query) use ($teamId) {
                $query->where('id', $teamId);
            })->where('id', $federationId)->with(['team', 'notifications.userNotification'])->first()->toArray();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation get ' . $federationId,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $federations,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            //\Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/teams/{teamId}/federations",
     *    operationId="create_federation_team",
     *    tags={"Team-Federations"},
     *    summary="FederationController@store",
     *    description="Create federation",
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
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="federation_type", type="string", example="federation type"),
     *             @OA\Property(property="auth_type", type="string", example="bearer"),
     *             @OA\Property(property="auth_secret_key", type="string", example="path/for/secret/key"),
     *             @OA\Property(property="endpoint_baseurl", type="string", example="https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app"),
     *             @OA\Property(property="endpoint_datasets", type="string", example="/api/v1/bearer/datasets"),
     *             @OA\Property(property="endpoint_dataset", type="string", example="/api/v1/bearer/datasets/{id}"),
     *             @OA\Property(property="run_time_hour", type="integer", example=11),
     *             @OA\Property(property="enabled", type="boolean", example=true),
     *             @OA\Property(property="notifications", type="array", example="['t1@test.com','t2@test.com']", @OA\Items(type="array", @OA\Items())),
     *             @OA\Property(property="tested", type="boolean", example=true),
     *          )
     *       )
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
     */
    public function store(CreateFederation $request, int $teamId)
    {
        // $loggingContext = $this->getLoggingContext($request);
        // $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $payload = [
                'federation_type' => $input['federation_type'],
                'auth_type' => $input['auth_type'],
                'auth_secret_key_location' => null,
                'endpoint_baseurl' => $input['endpoint_baseurl'],
                'endpoint_datasets' => $input['endpoint_datasets'],
                'endpoint_dataset' => $input['endpoint_dataset'],
                'run_time_hour' => $input['run_time_hour'],
                'enabled' => $input['enabled'],
                'tested' => array_key_exists('tested', $input) ? $input['tested'] : 0,
            ];

            $federation = Federation::create($payload);

            $secrets_payload = $this->getSecretsPayload($input);

            if ($secrets_payload) {
                $auth_secret_key_location = env('GOOGLE_SECRETS_GMI_PREPEND_NAME') . $federation->id;
                $payload = [
                    "path" => env('GOOGLE_APPLICATION_PROJECT_PATH'),
                    "secret_id" => $auth_secret_key_location,
                    "payload" => json_encode($secrets_payload)
                ];
                $response = Http::post(env('GMI_SERVICE_URL') . '/federation', $payload);
                //$response = Http::withHeaders($loggingContext)->post(env('GMI_SERVICE_URL') . '/federation', $payload);

                if (!$response->successful()) {
                    Federation::where('id', $federation->id)->delete();
                    return response()->json([
                        'message' => 'failed to save secrets for this federation',
                        'details' => $response->json(),
                    ], 400);
                }

                Federation::where('id', $federation->id)->first()
                    ->update(["auth_secret_key_location" => $auth_secret_key_location]);

            }

            TeamHasFederation::create([
                'federation_id' => $federation->id,
                'team_id' => $teamId,
            ]);

            foreach ($input['notifications'] as $notification) {
                // $notification may be a user id, or it may be an email address.
                $notification = Notification::create([
                    'notification_type' => 'federation',
                    'message' => '',
                    'opt_in' => 0,
                    'enabled' => 1,
                    'email' => is_numeric($notification) ? null : $notification,
                    'user_id' => is_numeric($notification) ? (int) $notification : null,
                ]);

                FederationHasNotification::create([
                    'federation_id' => $federation->id,
                    'notification_id' => $notification->id,
                ]);
            }

            $this->sendEmail($federation->id, 'CREATE');

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation ' . $federation->id . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $federation->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            // \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/teams/{teamId}/federations/{federationId}",
     *    operationId="update_federation_team",
     *    tags={"Team-Federations"},
     *    summary="FederationController@update",
     *    description="Update federation for team",
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
     *       name="federationId",
     *       in="path",
     *       description="federation id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="federation id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="federation_type", type="string", example="federation type"),
     *             @OA\Property(property="auth_type", type="string", example="bearer"),
     *             @OA\Property(property="auth_secret_key", type="string", example="path/for/secret/key"),
     *             @OA\Property(property="endpoint_baseurl", type="string", example="https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app"),
     *             @OA\Property(property="endpoint_datasets", type="string", example="/api/v1/bearer/datasets"),
     *             @OA\Property(property="endpoint_dataset", type="string", example="/api/v1/bearer/datasets/{id}"),
     *             @OA\Property(property="run_time_hour", type="integer", example=11),
     *             @OA\Property(property="run_time_minute", type="string", example=02),
     *             @OA\Property(property="enabled", type="boolean", example=true),
     *             @OA\Property(property="notifications", type="array", example="['t1@test.com','t2@test.com']", @OA\Items(type="array", @OA\Items())),
     *             @OA\Property(property="tested", type="boolean", example=true),
     *          )
     *       )
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
     */
    public function update(UpdateFederation $request, int $teamId, int $federationId)
    {
        // $loggingContext = $this->getLoggingContext($request);
        // $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $updateArray = [
                'federation_type' => $input['federation_type'],
                'auth_type' => $input['auth_type'],
                'endpoint_baseurl' => $input['endpoint_baseurl'],
                'endpoint_datasets' => $input['endpoint_datasets'],
                'endpoint_dataset' => $input['endpoint_dataset'],
                'run_time_hour' => $input['run_time_hour'],
                'run_time_minute' => $input['run_time_minute'],
                'enabled' => $input['enabled'],
                'tested' => array_key_exists('tested', $input) ? $input['tested'] : 0
            ];

            Federation::where('id', $federationId)->update($updateArray);

            $secrets_payload = $this->getSecretsPayload($input);
            if ($secrets_payload) {
                $auth_secret_key_location = env('GOOGLE_SECRETS_GMI_PREPEND_NAME') . $federationId;
                $payload = [
                    "path" => env('GOOGLE_APPLICATION_PROJECT_PATH'),
                    "secret_id" => $auth_secret_key_location,
                    "payload" => json_encode($secrets_payload)
                ];

                $response = Http::patch(env('GMI_SERVICE_URL') . '/federation', $payload);

                //$response = Http::withHeaders($loggingContext)->patch(env('GMI_SERVICE_URL') . '/federation', $payload);

                if (!$response->successful()) {
                    return response()->json([
                        'message' => 'something gone wrong with updating federation secret key',
                        'details' => $response->json(),
                    ], 400);
                }

            }

            $federationNotifications = FederationHasNotification::where([
                'federation_id' => $federationId,
            ])->pluck('notification_id');

            foreach ($federationNotifications as $federationNotification) {
                Notification::where('id', $federationNotification)->delete();
                FederationHasNotification::where('notification_id', $federationNotification)->delete();
            }

            foreach ($input['notifications'] as $notification) {
                // $notification may be a user id, or it may be an email address.
                $notification = Notification::create([
                    'notification_type' => 'federation',
                    'message' => '',
                    'opt_in' => 0,
                    'enabled' => 1,
                    'email' => is_numeric($notification) ? null : $notification,
                    'user_id' => is_numeric($notification) ? (int) $notification : null,
                ]);

                FederationHasNotification::create([
                    'federation_id' => $federationId,
                    'notification_id' => $notification->id,
                ]);
            }

            $response = Federation::where('id', '=', $federationId)
                ->whereHas('team', function ($query) use ($teamId) {
                    $query->where('id', $teamId);
                })->with(['notifications'])->first();

            $this->sendEmail($federationId, 'UPDATE');

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation ' . $federationId . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $response,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            // \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/teams/{teamId}/federations/{federationId}",
     *    operationId="edit_federation_team",
     *    tags={"Team-Federations"},
     *    summary="FederationController@edit",
     *    description="Edit federation for team",
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
     *       name="federationId",
     *       in="path",
     *       description="federation id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="federation id",
     *       ),
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="federation_type", type="string", example="federation type"),
     *             @OA\Property(property="auth_type", type="string", example="bearer"),
     *             @OA\Property(property="auth_secret_key", type="string", example="path/for/secret/key"),
     *             @OA\Property(property="endpoint_baseurl", type="string", example="https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app"),
     *             @OA\Property(property="endpoint_datasets", type="string", example="/api/v1/bearer/datasets"),
     *             @OA\Property(property="endpoint_dataset", type="string", example="/api/v1/bearer/datasets/{id}"),
     *             @OA\Property(property="run_time_hour", type="integer", example=11),
     *             @OA\Property(property="enabled", type="boolean", example=true),
     *             @OA\Property(property="notifications", type="array", example="['t1@test.com','t2@test.com']", @OA\Items(type="array", @OA\Items())),
     *             @OA\Property(property="tested", type="boolean", example=true),
     *          )
     *       )
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
     */
    public function edit(EditFederation $request, int $teamId, int $federationId)
    {
        // $loggingContext = $this->getLoggingContext($request);
        // $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $arrayKeys = [
                'federation_type',
                'auth_type',
                'auth_secret_key',
                'endpoint_baseurl',
                'endpoint_datasets',
                'endpoint_dataset',
                'run_time_hour',
                'enabled',
                'tested',
            ];

            $updateArray = $this->checkEditArray($input, $arrayKeys);
            unset($updateArray['auth_secret_key']);
            Federation::where('id', $federationId)->update($updateArray);

            $secrets_payload = $this->getSecretsPayload($input);
            if ($secrets_payload) {
                $auth_secret_key_location = env('GOOGLE_SECRETS_GMI_PREPEND_NAME') . $federationId;
                $payload = [
                    "path" => env('GOOGLE_APPLICATION_PROJECT_PATH'),
                    "secret_id" => $auth_secret_key_location,
                    "payload" => json_encode($secrets_payload)
                ];

                $response = Http::patch(env('GMI_SERVICE_URL') . '/federation', $payload);
                //$response = Http::withHeaders($loggingContext)->patch(env('GMI_SERVICE_URL') . '/federation', $payload);

                if (!$response->successful()) {
                    return response()->json([
                        'message' => 'something gone wrong with updating federation secret key',
                        'details' => $response->json(),
                    ], 400);
                }

            }

            if (array_key_exists('notifications', $input)) {
                $federationNotifications = FederationHasNotification::where([
                    'federation_id' => $federationId,
                ])->pluck('notification_id');

                foreach ($federationNotifications as $federationNotification) {
                    Notification::where('id', $federationNotification)->delete();
                    FederationHasNotification::where('notification_id', $federationNotification)->delete();
                }

                foreach ($input['notifications'] as $notification) {
                    // $notification may be a user id, or it may be an email address.
                    $notification = Notification::create([
                        'notification_type' => 'federation',
                        'message' => '',
                        'opt_in' => 0,
                        'enabled' => 1,
                        'email' => is_numeric($notification) ? null : $notification,
                        'user_id' => is_numeric($notification) ? (int) $notification : null,
                    ]);

                    FederationHasNotification::create([
                        'federation_id' => $federationId,
                        'notification_id' => $notification->id,
                    ]);
                }
            }

            $response = Federation::where('id', '=', $federationId)
                ->whereHas('team', function ($query) use ($teamId) {
                    $query->where('id', $teamId);
                })->with(['notifications'])->first();

            $this->sendEmail($federationId, 'UPDATE');

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation ' . $federationId . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $response
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            // \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/teams/{teamId}/federations/{federationId}",
     *    operationId="delete_federation",
     *    tags={"Team-Federations"},
     *    summary="FederationController@destroy",
     *    description="Delete federation for team",
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
     *       name="federationId",
     *       in="path",
     *       description="federation id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="federation id",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       )
     *    ),
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
     *    ),
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
     */
    public function destroy(DeleteFederation $request, int $teamId, int $federationId)
    {
        // $loggingContext = $this->getLoggingContext($request);
        // $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $federationNotifications = FederationHasNotification::where([
                'federation_id' => $federationId,
            ])->pluck('notification_id');

            foreach ($federationNotifications as $federationNotification) {
                Notification::where('id', $federationNotification)->delete();
                FederationHasNotification::where('notification_id', $federationNotification)->delete();
            }

            Federation::where('id', $federationId)->delete();

            TeamHasFederation::where([
                'federation_id' => $federationId,
                'team_id' => $teamId,
            ])->delete();

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation ' . $federationId . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            // \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\POST(
     *    path="/api/v1/teams/{teamId}/federations/test",
     *    operationId="test_federation",
     *    tags={"Team-Federations"},
     *    summary="FederationController@testFederation",
     *    description="Test federation configuration",
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
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="boolean", example="false"),
     *          @OA\Property(property="errors", type="string", example="request received HTTP 401 (Unauthorized)"),
     *          @OA\Property(property="status", type="integer", example="401"),
     *          @OA\Property(property="title", type="string", example="Test Unsuccessful"),
     *       )
     *    )
     * )
     */
    public function testFederation(Request $request)
    {
        // $loggingContext = $this->getLoggingContext($request);
        // $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();

        try {
            $response = Http::post(env('GMI_SERVICE_URL') . '/test', $input);

            // $response = Http::withHeaders($loggingContext)->post(env('GMI_SERVICE_URL') . '/test', $input);
            return response()->json([
                'data' => $response->json(),
            ], 200);

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            // \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    private function getSecretsPayload(array $input)
    {
        $secrets_payload = [];
        $secret_key = '';
        if (in_array($input['auth_type'], ['BEARER', 'API_KEY'])) {
            $secret_key = $input['auth_secret_key'];
        }
        switch ($input['auth_type']) {
            case 'BEARER':
                $secrets_payload = [
                    "bearer_token" => $secret_key
                ];
                break;
            case 'API_KEY':
                $secrets_payload = [
                    "api_key" => $secret_key,
                    "client_id" => "", //something needs to happen here??
                    "client_secret" => "" //something needs to happen here??
                ];
                break;
            case 'NO_AUTH':
                $secrets_payload = null;
                break;
        }
        return $secrets_payload;
    }

    // send email
    public function sendEmail(int $federationId, string $type)
    {
        $federation = Federation::where('id', $federationId)
            ->with(['team','notifications.userNotification'])
            ->first();
        if (is_null($federation)) {
            throw new Exception('Gateway App not found!');
        }

        $template = null;
        switch ($type) {
            case 'CREATE':
                $template = EmailTemplate::where('identifier', '=', 'federation.app.create')->first();
                break;
            case 'UPDATE':
                $template = EmailTemplate::where('identifier', '=', 'federation.app.update')->first();
                break;
            default:
                throw new Exception("Send email type not found!");
                break;
        }

        if (is_null($template)) {
            throw new Exception('Email template not found!');
        }

        $receivers = $this->sendEmailTo($federationId);

        foreach ($receivers as $receiver) {
            $to = [
                'to' => [
                    'email' => $receiver['email'],
                    'name' => $receiver['name'],
                ],
            ];

            $replacements = [
                '[[TEAM_ID]]' => $federation->team[0]['id'],
                '[[TEAM_NAME]]' => $federation->team[0]['name'],
                '[[USER_FIRSTNAME]]' => $receiver['firstname'],
                '[[FEDERATION_NAME]]' => 'Integration ' . $federation->federation_type,
                '[[FEDERATION_CREATED_AT_DATE]]' => $federation->created_at,
                '[[FEDERATION_UPDATED_AT_DATE]]' => $federation->updated_at,
                '[[FEDERATION_STATUS]]' => $federation->enabled ? 'enabled' : 'disabled',
                '[[CURRENT_YEAR]]' => date('Y'),
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        }

    }

    public function sendEmailTo(int $federationId): array
    {
        $return = [];

        $federation = Federation::where('id', $federationId)->first();
        if (is_null($federation)) {
            return $return;
        }

        $teamHasFederation = TeamHasFederation::where('federation_id', $federationId)->first();
        if (is_null($teamHasFederation)) {
            return $return;
        }
        $teamId = $teamHasFederation->team_id;

        // only for users with the following roles: 'custodian.team.admin', 'developer'
        $roles = Role::whereIn('name', ['custodian.team.admin', 'developer'])->select('id')->get();
        $roles = convertArrayToArrayWithKeyName($roles, 'id');
        $teamHasUsers = TeamHasUser::where('team_id', $teamId)->select('id', 'user_id')->get();

        $notificationuserId = [];
        foreach ($teamHasUsers as $item) {
            $teamUserHasRoles = TeamUserHasRole::whereIn('role_id', $roles)->where('team_has_user_id', $item->id)->first();
            if (!is_null($teamUserHasRoles)) {
                $notificationuserId[] = $item->user_id;
            }
        }

        $notificationuserId = array_unique($notificationuserId);
        $return = User::whereIn('id', $notificationuserId)->select(['firstname', 'name', 'email'])->get()->toArray();

        return $return;
    }

}
