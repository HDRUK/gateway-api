<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\FederationSecretException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Federation\CreateFederation;
use App\Http\Requests\Federation\DeleteFederation;
use App\Http\Requests\Federation\GetAllFederation;
use App\Http\Requests\Federation\GetFederation;
use App\Http\Requests\Federation\GetFederationHistory;
use App\Http\Requests\Federation\RunNowFederation;
use App\Http\Requests\Federation\UpdateFederation;
use App\Http\Traits\LoggingContext;
use App\Jobs\TestFederation;
use App\Services\FederationService;
use Auditor;
use Config;
use Exception;
use Illuminate\Http\Request;

class FederationController extends Controller
{
    use LoggingContext;

    public function __construct(
        private readonly FederationService $federationService,
    ) {
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
     *                   @OA\Property(property="enabled_at", type="datetime", example="2023-04-03 12:00:00", nullable=true),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="tested", type="boolean", example="0"),
     *                   @OA\Property(property="is_running", type="boolean", example="0"),
     *                   @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="last_run_at", type="datetime", example="2026-07-25 09:12:04", nullable=true),
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
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $jwtUser = $request->jwtUser();

        try {
            $perPage = request('per_page', Config::get('constants.per_page'));
            $federations = $this->federationService->listForTeam($teamId, $perPage);

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
            \Log::info($e->getMessage(), $loggingContext);

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
     *              @OA\Property(property="enabled_at", type="datetime", example="2023-04-03 12:00:00", nullable=true),
     *              @OA\Property(property="counter", type="integer", example="34319"),
     *              @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *              @OA\Property(property="tested", type="boolean", example="0"),
     *              @OA\Property(property="notifications", type="array", example="[]", @OA\Items()),
     *              @OA\Property(property="is_running", type="boolean", example="0"),
     *           ),
     *        ),
     *    ),
     * )
     */
    public function show(GetFederation $request, int $teamId, int $federationId)
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $jwtUser = $request->jwtUser();

        try {
            $federation = $this->federationService->getForTeam($teamId, $federationId);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation get ' . $federationId,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $federation,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

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
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = $request->jwtUser();

        try {
            $federation = $this->federationService->create($teamId, $input);

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
        } catch (FederationSecretException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'details' => $e->getDetails(),
            ], 400);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

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
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();
        $jwtUser = $request->jwtUser();

        try {
            $response = $this->federationService->update($teamId, $federationId, $input);

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
        } catch (FederationSecretException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'details' => $e->getDetails(),
            ], 400);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

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
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $jwtUser = $request->jwtUser();

        try {
            $this->federationService->delete($teamId, $federationId, $loggingContext);

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
            \Log::info($e->getMessage(), $loggingContext);

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
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $input = $request->all();

        try {
            $testVerdict = new TestFederation($input);
            return $testVerdict->handle();
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\GET(
     *    path="/api/v1/teams/{teamId}/federations/{federationId}/run",
     *    operationId="run_federation",
     *    tags={"Team-Federations"},
     *    summary="FederationController@runNow",
     *    description="Run federation immediately",
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
     *          @OA\Property(property="message", type="boolean", example="false"),
     *          @OA\Property(property="errors", type="string", example="request received HTTP 401 (Unauthorized)"),
     *          @OA\Property(property="status", type="integer", example="401"),
     *          @OA\Property(property="title", type="string", example="Test Unsuccessful"),
     *       )
     *    )
     * )
     */
    public function runNow(RunNowFederation $request, int $teamId, int $federationId)
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $jwtUser = $request->jwtUser();

        try {
            $this->federationService->runNow($federationId);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'RUN_NOW',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation ' . $federationId . ' run now',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/teams/{teamId}/federations/{federationId}/history",
     *    operationId="get_federation_history",
     *    tags={"Team-Federations"},
     *    summary="FederationController@history",
     *    description="Get run history for a federation",
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
     *    @OA\Parameter(
     *       name="per_page",
     *       in="query",
     *       description="per page",
     *       required=false,
     *       example="25",
     *       @OA\Schema(
     *          type="integer",
     *          description="per page",
     *       ),
     *    ),
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *       @OA\JsonContent(
     *          @OA\Property(property="current_page", type="integer", example="1"),
     *          @OA\Property(property="data", type="array",
     *             @OA\Items(type="object",
     *                @OA\Property(property="job_uuid", type="string", example="6d6b0e2e-6e4a-4a63-8f3c-2f9d9c8a1e11"),
     *                @OA\Property(property="started_at", type="datetime", example="2025-03-13 14:00:00"),
     *                @OA\Property(property="finished_at", type="datetime", example="2025-03-13 14:02:31"),
     *                @OA\Property(property="status", type="string", example="failed", enum={"success", "failed", "in_progress"}),
     *                @OA\Property(property="message", type="string", example="2 of 5 datasets failed", nullable=true),
     *                @OA\Property(property="failed_datasets", type="array",
     *                   @OA\Items(type="object",
     *                      @OA\Property(property="pid", type="string", example="9c1e2f3a-...-abcdef"),
     *                      @OA\Property(property="message", type="string", example="HDRUK/2.0.2: must NOT have additional properties"),
     *                   ),
     *                ),
     *             ),
     *          ),
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams\/19\/federations\/1\/history?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams\/19\/federations\/1\/history?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/teams\/19\/federations\/1\/history"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *       ),
     *    ),
     * )
     */
    public function history(GetFederationHistory $request, int $teamId, int $federationId)
    {
        $loggingContext = $this->getLoggingContext($request);
        $loggingContext['method_name'] = class_basename($this) . '@' . __FUNCTION__;

        $jwtUser = $request->jwtUser();

        try {
            $perPage = $request->validated('per_page', Config::get('constants.per_page'));
            $executions = $this->federationService->history($federationId, $perPage);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => 'Federation ' . $federationId . ' history',
            ]);

            return response()->json($executions);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            \Log::info($e->getMessage(), $loggingContext);

            throw new Exception($e->getMessage());
        }
    }
}
