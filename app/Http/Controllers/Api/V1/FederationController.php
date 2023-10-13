<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use App\Models\Team;
use App\Models\Federation;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\TeamHasFederation;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;
use App\Models\FederationHasNotification;
use App\Http\Traits\RequestTransformation;
use App\Http\Traits\FederationTransformation;
use App\Http\Requests\Federation\GetFederation;
use App\Http\Requests\Federation\EditFederation;
use App\Http\Requests\Federation\CreateFederation;
use App\Http\Requests\Federation\DeleteFederation;
use App\Http\Requests\Federation\GetAllFederation;
use App\Http\Requests\Federation\UpdateFederation;

class FederationController extends Controller
{
    use FederationTransformation;
    use RequestTransformation;

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
        $federations = Federation::whereHas('team', function ($query) use ($teamId) {
            $query->where('id', $teamId);
        })->paginate(Config::get('constants.per_page'), ['*'], 'page');

        return response()->json(
            $federations,
        );
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
     *           ),
     *        ),
     *    ),
     * )
     */
    public function show(GetFederation $request, int $teamId, int $federationId)
    {
        $federations = Federation::whereHas('team', function ($query) use ($teamId) {
            $query->where('id', $teamId);
        })->where('id', $federationId)->first()->toArray();

        return response()->json([
            'message' => 'success',
            'data' => $federations,
        ], 200);
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
     *             @OA\Property(property="notification", type="array", example="['t1@test.com','t2@test.com']", @OA\Items(type="array", @OA\Items())),
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
        try {
            $input = $request->all();

            $federation = Federation::create([
                'federation_type' => $input['federation_type'],
                'auth_type' => $input['auth_type'],
                'auth_secret_key' => $input['auth_secret_key'],
                'endpoint_baseurl' => $input['endpoint_baseurl'],
                'endpoint_datasets' => $input['endpoint_datasets'],
                'endpoint_dataset' => $input['endpoint_dataset'],
                'run_time_hour' => $input['run_time_hour'],
                'enabled' => $input['enabled'],
            ]);

            TeamHasFederation::create([
                'federation_id' => $federation->id,
                'team_id' => $teamId,
            ]);

            foreach($input['notification'] as $email) {
                $notification = Notification::create([
                    'notification_type' => 'federation',
                    'message' => '',
                    'opt_in' => 0,
                    'enabled' => 1,
                    'email' => $email,
                ]);

                FederationHasNotification::create([
                    'federation_id' => $federation->id,
                    'notification_id' => $notification->id,
                ]);
            }

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $federation->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
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
     *             @OA\Property(property="enabled", type="boolean", example=true),
     *             @OA\Property(property="notification", type="array", example="['t1@test.com','t2@test.com']", @OA\Items(type="array", @OA\Items())),
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
        try {
            $input = $request->all();

            Federation::where('id', $federationId)->update([
                'federation_type' => $input['federation_type'],
                'auth_type' => $input['auth_type'],
                'auth_secret_key' => $input['auth_secret_key'],
                'endpoint_baseurl' => $input['endpoint_baseurl'],
                'endpoint_datasets' => $input['endpoint_datasets'],
                'endpoint_dataset' => $input['endpoint_dataset'],
                'run_time_hour' => $input['run_time_hour'],
                'enabled' => $input['enabled'],
            ]);

            $federationNotifications = FederationHasNotification::where([
                'federation_id' => $federationId,
            ])->pluck('notification_id');

            foreach ($federationNotifications as $federationNotification) {
                Notification::where('id', $federationNotification)->delete();
                FederationHasNotification::where('notification_id', $federationNotification)->delete();
            }

            foreach ($input['notification'] as $email) {
                $notification = Notification::create([
                    'notification_type' => 'federation',
                    'message' => '',
                    'opt_in' => 0,
                    'enabled' => 1,
                    'email' => $email,
                ]);

                FederationHasNotification::create([
                    'federation_id' => $federationId,
                    'notification_id' => $notification->id,
                ]);
            }

            $teamFederations = Team::where('id', $teamId)->with(['federation'])->get()->toArray();
            $response = $this->getFederation($teamFederations, $federationId);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $response,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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
     *             @OA\Property(property="notification", type="array", example="['t1@test.com','t2@test.com']", @OA\Items(type="array", @OA\Items())),
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
        try {
            $input = $request->all();

            $input = $request->all();
            $arrayKeys = [
                'federation_type',
                'auth_type',
                'auth_secret_key',
                'endpoint_baseurl',
                'endpoint_datasets',
                'endpoint_dataset',
                'run_time_hour',
                'enabled',
            ];

            $array = $this->checkEditArray($input, $arrayKeys);

            Federation::where('id', $federationId)->update($array);

            if (array_key_exists('notification', $input)) {
                $federationNotifications = FederationHasNotification::where([
                    'federation_id' => $federationId,
                ])->pluck('notification_id');

                foreach ($federationNotifications as $federationNotification) {
                    Notification::where('id', $federationNotification)->delete();
                    FederationHasNotification::where('notification_id', $federationNotification)->delete();
                }

                foreach ($input['notification'] as $email) {
                    $notification = Notification::create([
                        'notification_type' => 'federation',
                        'message' => '',
                        'opt_in' => 0,
                        'enabled' => 1,
                        'email' => $email,
                    ]);

                    FederationHasNotification::create([
                        'federation_id' => $federationId,
                        'notification_id' => $notification->id,
                    ]);
                }
            }

            $teamFederations = Team::where('id', $teamId)->with(['federation'])->get()->toArray();
            $response = $this->getFederation($teamFederations, $federationId);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $response
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
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

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
