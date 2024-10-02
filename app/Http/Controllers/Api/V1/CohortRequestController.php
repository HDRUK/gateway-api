<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use CloudLogger;
use App\Models\User;
use App\Jobs\SendEmailJob;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\CohortRequest;
use App\Models\EmailTemplate;
use Illuminate\Support\Carbon;
use App\Models\CohortRequestLog;
use Illuminate\Http\JsonResponse;
use App\Models\CohortRequestHasLog;
use App\Http\Controllers\Controller;
use App\Http\Traits\HubspotContacts;
use App\Models\CohortRequestHasPermission;
use App\Http\Requests\CohortRequest\GetCohortRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Requests\CohortRequest\CreateCohortRequest;
use App\Http\Requests\CohortRequest\DeleteCohortRequest;
use App\Http\Requests\CohortRequest\UpdateCohortRequest;
use App\Http\Requests\CohortRequest\AssignAdminCohortRequest;
use App\Http\Requests\CohortRequest\RemoveAdminCohortRequest;
use Illuminate\Http\RedirectResponse;

class CohortRequestController extends Controller
{
    use HubspotContacts;

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
     *       name="request_status",
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
     *    @OA\Parameter(
     *       name="text",
     *       in="query",
     *       description="filter by organisation or user name",
     *       example="test",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by organisation or user name",
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
     *                   @OA\Property(property="accept_declaration", type="boolean", example="0"),
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $sort = [];
            $sortArray = $request->has('sort') ? explode(',', $request->query('sort', '')) : [];
            foreach ($sortArray as $item) {
                $tmp = explode(":", $item);
                $sort[$tmp[0]] = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';
            }

            $query = CohortRequest::with(['user', 'logs', 'logs.user', 'permissions']);

            // filter by users.email
            $query->filterByEmail($request->has('email') ? $request->query('email') : '');

            // filter by users.organisation
            $query->filterByOrganisation($request->has('organisation') ? $request->query('organisation') : '');

            // filter by users.name
            $query->filterByUserName($request->has('name') ? $request->query('name') : '');

            // filter by request_status
            if ($request->has('request_status')) {
                $query->where('request_status', strtoupper($request->query('request_status')));
            }

            // filter by users.organisation or users.name
            if ($request->has('text')) {
                $query->filterByOrganisationOrName($request->query('text'));
            }

            $query->join('users', 'cohort_requests.user_id', '=', 'users.id');

            foreach($sort as $key => $value) {
                if (in_array($key, ['created_at', 'updated_at', 'request_status'])) {
                    $query->orderBy('cohort_requests.' . $key, strtoupper($value));
                }

                if (in_array($key, ['name', 'organisation'])) {
                    $query->orderBy('users.' . $key, strtoupper($value));
                }
            }

            $query->select('cohort_requests.*');

            $cohortRequests = $query->paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request get all',
            ]);

            return response()->json(
                $cohortRequests
            );
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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $cohortRequests = CohortRequest::where('id', $id)
                ->with([
                    'user',
                    'logs' => function ($q) {
                        $q->orderBy('id', 'desc');
                    },
                    'logs.user',
                    'permissions',
                    ])
                ->first()->toArray();

            if (isset($cohortRequests['logs'])) {
                foreach ($cohortRequests['logs'] as &$log) {
                    $log['details'] = html_entity_decode($log['details']);
                }
            }

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request get ' . $id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $cohortRequests,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request get ' . $id,
            ]);

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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $id = 0;
            $cohortRequest = null;
            $notAllowUpdateRequest = ['PENDING', 'APPROVED', 'BANNED', 'SUSPENDED'];

            $checkRequestByUserId = CohortRequest::where([
                'user_id' => (int) $jwtUser['id'],
            ])->first();

            // keep just one request by user id
            if ($checkRequestByUserId && in_array(strtoupper($checkRequestByUserId['request_status']), $notAllowUpdateRequest)) {
                throw new Exception("A cohort request already exists or the status of the request does not allow updating.");
            } else {
                $id = $checkRequestByUserId ? $checkRequestByUserId->id : 0;
            }

            if ($id) {
                CohortRequest::where('id', $id)->update([
                    'user_id' => (int) $jwtUser['id'],
                    'request_status' => 'PENDING',
                    'cohort_status' => false,
                    'request_expire_at' => null,
                    'created_at' => Carbon::today()->toDateTimeString(),
                ]);
                CohortRequestHasPermission::where('id', $id)->delete();
            } else {
                $cohortRequest = CohortRequest::create([
                    'user_id' => (int) $jwtUser['id'],
                    'request_status' => 'PENDING',
                    'cohort_status' => false,
                    'created_at' => Carbon::now(),
                ]);
            }

            $cohortRequestLog = CohortRequestLog::create([
                'user_id' => (int) $jwtUser['id'],
                'details' => $input['details'],
                'request_status' => 'PENDING',
            ]);

            CohortRequestHasLog::create([
                'cohort_request_id' => $id ?: $cohortRequest->id,
                'cohort_request_log_id' => $cohortRequestLog->id,
            ]);

            // send email notification
            $this->sendEmail($cohortRequest->id);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request ' . ($id ?: $cohortRequest->id) . ' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $id ?: $cohortRequest->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));

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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $requestStatus = strtoupper(trim($input['request_status']));

            $currCohortRequest = CohortRequest::where('id', $id)->first();
            $currRequestStatus = strtoupper(trim($currCohortRequest['request_status']));

            $cohortRequestLog = new CohortRequestLog([
                'user_id' => (int) $jwtUser['id'],
                'details' => $input['details'],
                'request_status' => $requestStatus,
            ]);
            $cohortRequestLog->save();

            CohortRequestHasLog::create([
                'cohort_request_id' => $id,
                'cohort_request_log_id' => $cohortRequestLog->getKey(),
            ]);

            // APPROVED / BANNED / SUSPENDED
            // PENDING - initial state
            // EXPIRED - must be an update using the chron
            if ($currRequestStatus !== $requestStatus) {
                CohortRequest::where('id', $id)->update([
                    'request_status' => $requestStatus,
                    'cohort_status' => true,
                    'request_expire_at' => ($requestStatus !== 'APPROVED') ? null : Carbon::now()->addDays(Config::get('cohort.cohort_access_expiry_time_in_days')),
                    'accept_declaration' => $requestStatus === 'APPROVED',
                ]);
            }

            switch ($requestStatus) {
                case 'PENDING':
                case 'REJECTED':
                case 'SUSPENDED':
                    CohortRequestHasPermission::where('cohort_request_id', $id)->delete();
                    break;
                case 'APPROVED':
                    CohortRequestHasPermission::where('cohort_request_id', $id)->delete();
                    $permissions = Permission::where([
                        'application' => 'cohort',
                        'name' => 'GENERAL_ACCESS',
                    ])->first();
                    CohortRequestHasPermission::create([
                        'cohort_request_id' => $id,
                        'permission_id' => $permissions->id,
                    ]);
                    break;
                case 'BANNED':
                    CohortRequestHasPermission::where('cohort_request_id', $id)->delete();
                    $permissions = Permission::where([
                        'application' => 'cohort',
                        'name' => 'BANNED',
                    ])->first();
                    CohortRequestHasPermission::create([
                        'cohort_request_id' => $id,
                        'permission_id' => $permissions->id,
                    ]);
                    break;
                default:
                    throw new Exception("A cohort request status received not accepted.");
                    break;
            }

            $this->sendEmail($id);
            $this->updateOrCreateContact((int) $jwtUser['id']);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request ' . $id . ' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => CohortRequest::where('id', $id)->first()
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request ' . $id . ' updated',
            ]);

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
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        try {
            $cohortRequest = CohortRequest::withTrashed()->findOrFail($id);
            $cohortRequest->update(['accept_declaration' => false]);
            $cohortRequest->delete();

            CohortRequestHasPermission::where('id', $id)->delete();

            $this->updateOrCreateContact($id);

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/cohort_requests/export",
     *    operationId="export_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@export",
     *    description="Export a CSV file of the cohort request admin dashboard",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="request_status",
     *       in="query",
     *       description="filter by multiple statuses",
     *       example="pending,approved",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by multiple statuses",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="organisation",
     *       in="query",
     *       description="filter by multiple organisation names",
     *       example="Org%201,Organisation%20B",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by multiple organisation names",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="from",
     *       in="query",
     *       required=true,
     *       description="filter by date range - earliest date",
     *       example="2022-12-31",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by date range - earliest date",
     *       ),
     *    ),
     *    @OA\Parameter(
     *       name="to",
     *       in="query",
     *       required=true,
     *       description="filter by date range - latest date",
     *       example="2022-12-31",
     *       @OA\Schema(
     *          type="string",
     *          description="filter by date range - latest date",
     *       ),
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="CSV file",
     *       @OA\MediaType(
     *          mediaType="text/csv",
     *          @OA\Schema(
     *             type="string",
     *             example="""User ID"",Name,""Email address"",Organisation,Status,""Date Requested"",""Date Actioned""\n13,""Jackson Graham"",wmoen@example.com,""UK Health"",PENDING,""2023-09-17 13:31:25"",""2023-11-17 16:02:36""",
     *          )
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    )
     * )
     */
    public function export(Request $request): StreamedResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $query = CohortRequest::with(['user', 'logs', 'logs.user']);

            // filter by users.organisation
            if ($request->has('organisation')) {
                $organisationArray = explode(',', $request->query('organisation', ''));
                $organisationArrayUpper = array_map('strtoupper', $organisationArray);
                $query->filterByMultiOrganisation($organisationArrayUpper);
            }

            // filter by request_status. Convert to uppercase for comparison.
            if ($request->has('request_status')) {
                $requestStatusArray = explode(',', $request->query('request_status', ''));
                $requestStatusArrayUpper = array_map('strtoupper', $requestStatusArray);
                $query->filterByMultiRequestStatus($requestStatusArrayUpper);
            }

            // filter by provided date range
            $fromDate = Carbon::parse($request->query('from'));
            // add one day to get inclusive behaviour on $toDate
            $toDate = Carbon::parse($request->query('to'))->addDays(1);
            $query->filterBetween($fromDate, $toDate);

            $query->join('users', 'cohort_requests.user_id', '=', 'users.id');
            $query->orderBy('cohort_requests.created_at', 'asc');
            $result = $query->select('cohort_requests.*')->get();

            // callback function that writes to php://output
            $response = new StreamedResponse(
                function () use ($result) {

                    // Open output stream
                    $handle = fopen('php://output', 'w');

                    $headerRow = [
                        'User ID',
                        'Name',
                        'Email address',
                        'Organisation',
                        'Status',
                        'Date Requested',
                        'Date Actioned',
                        'Live',
                    ];

                    // Add CSV headers
                    fputcsv($handle, $headerRow);

                    // add the given number of rows to the file.
                    foreach ($result as $rowDetails) {
                        $row = [
                            (string)$rowDetails['user']['id'],
                            (string)$rowDetails['user']['name'],
                            (string)$rowDetails['user']['email'],
                            (string)$rowDetails['user']['organisation'],
                            (string)$rowDetails['request_status'],
                            (string)$rowDetails['created_at'],
                            (string)$rowDetails['updated_at'],
                            (string)$rowDetails['accept_declaration'],
                        ];
                        fputcsv($handle, $row);
                    }

                    // Close the output stream
                    fclose($handle);
                }
            );

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set(
                'Content-Disposition',
                'attachment;filename="Cohort_Discovery_Admin.csv"'
            );
            $response->headers->set('Cache-Control', 'max-age=0');

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXPORT',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request exported',
            ]);

            return $response;

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
     * @OA\POST(
     *    path="/api/v1/cohort_requests/{id}/admin",
     *    operationId="assing_admin_permission_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@assignAdminPermission",
     *    description="Assing admin permission for cohort request by id",
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
    public function assignAdminPermission(AssignAdminCohortRequest $request, int $id): JsonResponse
    {
        try {
            $input = $request->all();
            $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

            $permissionName = 'SYSTEM_ADMIN';
            $perms = Permission::where('name', $permissionName)->first();
            if (!$perms) {
                throw new Exception($permissionName  . ' permission not found!');
            }

            // assign role
            CohortRequestHasPermission::create([
                'cohort_request_id' => $id,
                'permission_id' => $perms->id,
            ]);

            // send email
            $this->sendEmail($id, 'assing');

            // update HubSpot
            $this->updateOrCreateContact($id);

            // Audit log
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request assign admin permission for id ' . $id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request exported',
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/cohort_requests/{id}/admin",
     *    operationId="remove_admin_permission_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@removeAdminPermission",
     *    description="Remove admin permission for cohort request by id",
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
    public function removeAdminPermission(RemoveAdminCohortRequest $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        $permissionName = 'SYSTEM_ADMIN';
        $perms = Permission::where('name', $permissionName)->first();
        if (!$perms) {
            throw new Exception($permissionName  . ' permission not found!');
        }

        try {
            // remove admin role
            CohortRequestHasPermission::where([
                'cohort_request_id' => $id,
                'permission_id' => $perms->id,
            ])->delete();

            // send email
            $this->sendEmail($id, 'remove');

            // update HubSpot
            $this->updateOrCreateContact($id);

            // Audit log
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Cohort Request remove admin permission for id ' . $id,
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

    /**
     * @OA\Get(
     *    path="/api/v1/cohort_requests/access",
     *    operationId="access_cohort_requests",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@checkAccess",
     *    description="access cohort request by jwt",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *      response=302,
     *      description="Redirect to external URL",
     *      @OA\Header(
     *         header="Location",
     *         description="URL to which the client should be redirected",
     *         @OA\Schema(
     *            type="string"
     *         )
     *       }
     *    )
     *    @OA\Response(
     *       response=500,
     *       description="Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Unauthorized for access :: The request is not approved"),
     *       )
     *    )
     * )
     */
    public function checkAccess(Request $request)
    {
        $origin = $request->headers->get('Origin');
        \Log::info('checkAccess origin :: ' . json_encode($request));
        \Log::info('checkAccess origin 2 :: ' . json_encode($request->headers->get('X-Forwarded-Host')));


        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        if (!array_key_exists('id', $jwtUser)) {
            throw new Exception('Unauthorized');
        }

        try {
            $userId = (int) $jwtUser['id'];

            $checkingCohortRequest = CohortRequest::where([
                'user_id' => $userId,
                'request_status' => 'APPROVED',
                'cohort_status' => 1,
            ])->first();

            if (!$checkingCohortRequest) {
                throw new Exception('Unauthorized for access :: The request is not approved');
            }

            $checkingCohortPerms = CohortRequestHasPermission::where([
                'cohort_request_id' => $checkingCohortRequest->id
            ])->count();

            if (!$checkingCohortPerms) {
                throw new Exception('Unauthorized for access :: There are not enough permissions allocated for the cohort request');
            }

            // save the user id in session
            session(['cr_uid' => $userId]);

            // delete after implementation
            \Log::info('cohort request access :: ' . json_encode([
                'userId' => $userId,
                'sessionId' => session()->getId()
            ]));
            // CloudLogger::write('cohort request access :: ' . json_encode([
            //     'userId' => $userId,
            //     'sessionId' => session()->getId()
            // ]));

            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'Access rquest for user',
            ]);

            $rquestInitUrl = Config::get('services.rquest.init_url');
            \Log::info('CohortRequestController checkAccess :: ' . json_encode($rquestInitUrl));

            // return new RedirectResponse($rquestInitUrl, 302);
            // return redirect()->away($rquestInitUrl);
            // return response()->json([
            //     'redirect_uri' => $rquestInitUrl,
            // ], Config::get('statuscodes.STATUS_OK.code'));
            // return redirect('https://rquest.test.healthdatagateway.org/bcrquest/')
            //     ->withHeaders([
            //         'Access-Control-Allow-Origin' => 'https://rquest.test.healthdatagateway.org',
            //         'Access-Control-Allow-Credentials' => 'true',
            //     ]);

            return response()->json([
                'redirect_uri' => $rquestInitUrl,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int)$jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Cohort Request send email :: ' . $e->getMessage());
        }
    }

    private function sendEmail($cohortId, $admin = null)
    {
        try {
            $cohort = CohortRequest::where('id', $cohortId)->first();
            $cohortRequestStatus = $cohort['request_status'];
            $cohortRequestUserId = $cohort['user_id'];
            $user = User::where('id', $cohortRequestUserId)->first();
            $userEmail = ($user['preferred_email'] === 'primary') ? $user['email'] : $user['secondary_email'];

            // template
            $template = null;
            if (!$admin) {
                switch ($cohortRequestStatus) {
                    case 'PENDING': // submitted
                        $template = EmailTemplate::where('identifier', '=', 'cohort.discovery.access.submitted')->first();
                        break;
                    case 'REJECTED':
                        $template = EmailTemplate::where('identifier', '=', 'cohort.discovery.access.rejected')->first();
                        break;
                    case 'APPROVED':
                        $template = EmailTemplate::where('identifier', '=', 'cohort.discovery.access.approved')->first();
                        break;
                    case 'BANNED':
                        $template = EmailTemplate::where('identifier', '=', 'cohort.discovery.access.banned')->first();
                        break;
                    case 'SUSPENDED':
                        $template = EmailTemplate::where('identifier', '=', 'cohort.discovery.access.suspended')->first();
                        break;
                }
            }

            if ($admin) {
                switch ($admin) {
                    case 'assign': // submitted
                        $template = EmailTemplate::where('identifier', '=', 'cohort.request.admin.approve')->first();
                        break;
                    case 'remove':
                        $template = EmailTemplate::where('identifier', '=', 'cohort.request.admin.remove')->first();
                        break;
                }
            }

            $to = [
                'to' => [
                    'email' => $userEmail,
                    'name' => $user['name'],
                ],
            ];

            $replacements = [
                '[[USER_FIRSTNAME]]' => $user['firstname'],
                '[[EXPIRE_DATE]]' => $cohort['request_expire_at'],
                '[[CURRENT_YEAR]]' => date("Y"),
                '[[USER_EMAIL]]' => $userEmail,
                '[[COHORT_DISCOVERY_ACCESS_URL]]' => Config::get('cohort.cohort_discovery_access_url'),
                '[[COHORT_DISCOVERY_USING_URL]]' => Config::get('cohort.cohort_discovery_using_url'),
                '[[COHORT_DISCOVERY_RENEW_URL]]' => Config::get('cohort.cohort_discovery_renew_url'),
            ];

            if ($template) {
                SendEmailJob::dispatch($to, $template, $replacements);
            }

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Cohort Request send email :: ' . $e->getMessage());
        }
    }

}
