<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Exception;
use Illuminate\Http\Request;
use App\Models\CohortRequest;
use Illuminate\Support\Carbon;
use App\Models\CohortRequestLog;
use Illuminate\Http\JsonResponse;
use App\Models\CohortRequestHasLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\CohortRequest\GetCohortRequest;
use App\Http\Requests\CohortRequest\CreateCohortRequest;
use App\Http\Requests\CohortRequest\DeleteCohortRequest;
use App\Http\Requests\CohortRequest\UpdateCohortRequest;

class CohortRequestController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * @OA\Get(
     *    path="/api/v1/cohort_requests",
     *    operationId="fetch_all_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@index",
     *    description="Returns a list of cohort requests",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="orderBy",
     *       in="query",
     *       description="Comma-separated list of fields to include in the response",
     *       example="orderBy=created_at:asc,updated_at:asc,organisation:desc,name:desc",
     *       @OA\Schema(
     *          type="string",
     *          description="Comma-separated list of fields",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="status",
     *       in="query",
     *       description="filter by status",
     *       example="test",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by status",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="name",
     *       in="query",
     *       description="filter by user name",
     *       example="test",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by user name",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="organisation",
     *       in="query",
     *       description="filter by organisation name",
     *       example="test",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by organisation name",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="email",
     *       in="query",
     *       description="filter by email",
     *       example="test",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by email",
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
     *                   @OA\Property(property="user_id", type="integer", example="1"),
     *                   @OA\Property(property="user", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="request_status", type="string", example="PENDING"),
     *                   @OA\Property(property="cohort_status", type="boolean", example="0"),
     *                   @OA\Property(property="request_expire_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="logs", type="array", example="[]", @OA\Items()),
     *                ),
     *             ),
     *          @OA\Property(property="first_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests?page=1"),
     *          @OA\Property(property="from", type="integer", example="1"),
     *          @OA\Property(property="last_page", type="integer", example="1"),
     *          @OA\Property(property="last_page_url", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests?page=1"),
     *          @OA\Property(property="links", type="array", example="[]", @OA\Items(type="array", @OA\Items())),
     *          @OA\Property(property="next_page_url", type="string", example="null"),
     *          @OA\Property(property="path", type="string", example="http:\/\/localhost:8000\/api\/v1\/cohort_requests"),
     *          @OA\Property(property="per_page", type="integer", example="25"),
     *          @OA\Property(property="prev_page_url", type="string", example="null"),
     *          @OA\Property(property="to", type="integer", example="3"),
     *          @OA\Property(property="total", type="integer", example="3"),
     *          ),
     *       ),
     *    ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orderBy = [];
            if ($request->has('orderBy')) {
                $orderByArray = explode(',', $request->query('orderBy', ''));
                if (count($orderBy)) {
                    foreach ($orderByArray as $item) {
                        list($field, $name) = explode(":", $item . ":asc", 2);
                        $orderBy[$field] = $name;
                    }
                }
            }

            $query = CohortRequest::with(['user', 'logs', 'logs.user']);

            // filter by users.email
            if ($request->has('email')) {
                $email = $request->query('email');
                $query->whereHas('user', function ($q) use ($email) {
                    $q->where('email', 'LIKE', '%' . $email . '%');
                });
            }

            // filter by users.organisation
            if ($request->has('organisation')) {
                $organisation = $request->query('organisation');
                $query->whereHas('user', function ($q) use ($organisation) {
                    $q->where('organisation', 'LIKE', '%' . $organisation . '%');
                });
            }

            // filter by users.name
            if ($request->has('name')) {
                $name = $request->query('name');
                $query->whereHas('user', function ($q) use ($name) {
                    $q->where('name', 'LIKE', '%' . $name . '%');
                });
            }

            // filter by request_status
            if ($request->has('status')) {
                $query->where('request_status', strtoupper($request->query('status')));
            }

            $query->join('users', 'cohort_requests.user_id', '=', 'users.id');

            if ($orderBy) {
                foreach($orderBy as $key => $value) {
                    if (in_array($key, ['created_at', 'updated_at', 'request_status'])) {
                        $query->orderBy('cohort_requests.' . $key, strtoupper($value));
                    }

                    if (in_array($key, ['name', 'organisation'])) {
                        $query->orderBy('users.' . $key, strtoupper($value));
                    }
                    
                }
            }

            $query->select('cohort_requests.*');

            $cohortRequests = $query->paginate(Config::get('constants.per_page'), ['*'], 'page');

            return response()->json(
                $cohortRequests
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/cohort_requests/{id}",
     *    operationId="get_cohort_request_by_id",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@show",
     *    description="Get cohort requests by cohort request id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
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
     *          )
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
     *        response=404,
     *        description="Not found response",
     *        @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="not found"),
     *        )
     *    )
     * )
     */
    public function show(GetCohortRequest $request, int $id): JsonResponse
    {
        try {
            $cohortRequests = CohortRequest::where('id', $id)
                ->with(['user', 'logs', 'logs.user'])
                ->first()->toArray();

            return response()->json([
                'message' => 'success',
                'data' => $cohortRequests,
            ], 200);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *    path="/api/v1/cohort_requests",
     *    operationId="create_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@store",
     *    description="Create a new cohort request",
     *    security={{"bearerAuth":{}}},
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="details", type="string", example="example"),
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
    public function store(CreateCohortRequest $request): JsonResponse
    {
        try {
            $id = 0;
            $cohortRequest = null;
            $notAllowUpdateRequest = ['PENDING', 'APPROVED', 'BANNED', 'SUSPENDED'];
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $checkRequestByUserId = CohortRequest::where([
                'user_id' => $jwtUser['id'],
            ])->first();

            // keep just one request by user id
            if ($checkRequestByUserId && in_array(strtoupper($checkRequestByUserId['request_status']), $notAllowUpdateRequest)) {
                throw new Exception("A cohort request already exists or the status of the request does not allow updating.");
            } else {
                $id = $checkRequestByUserId ? $checkRequestByUserId->id : 0;
            }

            if ($id) {
                CohortRequest::where('id', $id)->update([
                    'user_id' => $jwtUser['id'],
                    'request_status' => 'PENDING',
                    'cohort_status' => false,
                    'request_expire_at' => null,
                    'created_at' => Carbon::today()->toDateTimeString(),0
                ]);
            } else {
                $cohortRequest = CohortRequest::create([
                    'user_id' => $jwtUser['id'],
                    'request_status' => 'PENDING',
                    'cohort_status' => false,
                ]);
            }

            $cohortRequestLog = CohortRequestLog::create([
                'user_id' => $jwtUser['id'],
                'details' => $input['details'],
                'request_status' => 'PENDING',
            ]);

            CohortRequestHasLog::create([
                'cohort_request_id' => $id ?: $cohortRequest->id,
                'cohort_request_log_id' => $cohortRequestLog->id,
            ]);

            // send email notification

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $id ?: $cohortRequest->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/cohort_requests/{id}",
     *    operationId="update_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@update",
     *    description="Update cohort request",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
     *       )
     *    ),
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *       @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *             @OA\Property(property="request_status", type="string", example="APPROVED"),
     *             @OA\Property(property="details", type="string", example="example"),
     *          )
     *       )
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
     *          )
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
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="bad request"),
     *          )
     *      )
     * )
     */
    public function update(UpdateCohortRequest $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];
            $requestStatus = strtoupper($input['request_status']);

            $currCohortRequest = CohortRequest::where('id', $id)->first();
            $currRequestStatus = strtoupper($currCohortRequest['request_status']);
            $checkRequestStatus = ($currRequestStatus === $requestStatus) ? 1 : 0;

            $cohortRequestLog = CohortRequestLog::create([
                'user_id' => $jwtUser['id'],
                'details' => $input['details'],
                'request_status' => $requestStatus,
            ]);

            CohortRequestHasLog::create([
                'cohort_request_id' => $id,
                'cohort_request_log_id' => $cohortRequestLog->id,
            ]);

            // APPROVED / BANNED / SUSPENDED
            // PENDING - initial state
            // EXPIRED - must be an update using the chron
            if ($currRequestStatus !== $requestStatus) {
                CohortRequest::where('id', $id)->update([
                    'user_id' => $jwtUser['id'],
                    'request_status' => $requestStatus,
                    'cohort_status' => true,
                    'request_expire_at' => ($requestStatus !== 'APPROVED') ? null : Carbon::now()->addSeconds(env('COHORT_REQUEST_EXPIRATION')),
                ]);
            }
            
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => CohortRequest::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/cohort_requests/{id}",
     *    operationId="delete_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@destroy",
     *    description="Delete cohort request by id",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
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
    public function destroy(DeleteCohortRequest $request, int $id): JsonResponse
    {
        try {
            CohortRequest::where('id', $id)->delete();

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
