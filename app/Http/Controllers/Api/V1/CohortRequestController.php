<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CohortRequest\AssignAdminCohortRequest;
use App\Http\Requests\CohortRequest\CreateCohortRequest;
use App\Http\Requests\CohortRequest\DeleteCohortRequest;
use App\Http\Requests\CohortRequest\GetCohortRequest;
use App\Http\Requests\CohortRequest\RemoveAdminCohortRequest;
use App\Http\Requests\CohortRequest\UpdateCohortRequest;
use App\Http\Traits\HubspotContacts;
use App\Jobs\SendEmailJob;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestHasPermission;
use App\Models\CohortRequestLog;
use App\Models\EmailTemplate;
use App\Models\OauthClient;
use App\Models\OauthUser;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserHasWorkgroup;
use Auditor;
use Config;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     *
     *    @OA\Parameter(
     *       name="orderBy",
     *       in="query",
     *       description="Comma-separated list of fields to include in the response",
     *       example="orderBy=created_at:asc,updated_at:asc,organisation:desc,name:desc",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="Comma-separated list of fields",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="request_status",
     *       in="query",
     *       description="filter by status",
     *       example="test",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by status",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="name",
     *       in="query",
     *       description="filter by user name",
     *       example="test",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by user name",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="organisation",
     *       in="query",
     *       description="filter by organisation name",
     *       example="test",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by organisation name",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="email",
     *       in="query",
     *       description="filter by email",
     *       example="test",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by email",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="text",
     *       in="query",
     *       description="filter by organisation or user name",
     *       example="test",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by organisation or user name",
     *       ),
     *    ),
     *
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="current_page", type="integer", example="1"),
     *             @OA\Property(property="data", type="array",
     *
     *                @OA\Items(type="object",
     *
     *                   @OA\Property(property="id", type="integer", example="123"),
     *                   @OA\Property(property="user_id", type="integer", example="1"),
     *                   @OA\Property(property="request_status", type="string", example="PENDING"),
     *                   @OA\Property(property="request_expire_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="accept_declaration", type="boolean", example="0"),
     *                   @OA\Property(property="nhse_sde_request_status", type="string", example="APPROVED"),
     *                   @OA\Property(property="nhse_sde_requested_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="nhse_sde_self_declared_approved_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="nhse_sde_request_expire_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="nhse_sde_updated_at", type="datetime", example="2023-04-03 12:00:00"),
     *                   @OA\Property(property="name", type="string", example="Harry Potter"),
     *                   @OA\Property(property="organisation", type="string", example="HMRC"),
     *                   @OA\Property(property="sector_name", type="string", example=""),
     *                   @OA\Property(property="logs", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="permissions", type="array", example="[]", @OA\Items()),
     *                   @OA\Property(property="user", type="array", example="[]", @OA\Items()),
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
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        $email = $request->query('email', null);
        $organisation = $request->query('organisation', null);
        $name = $request->query('name', null);
        $status = $request->query('request_status', null);
        $nhseSdeStatus = $request->query('nhse_sde_request_status', null);
        $filterText = $request->query('text', null);

        try {
            $sort = [];
            $sortArray = $request->has('sort') ? explode(',', $request->query('sort', '')) : [];
            foreach ($sortArray as $item) {
                $tmp = explode(':', $item);
                $sort[$tmp[0]] = array_key_exists('1', $tmp) ? $tmp[1] : 'asc';
            }

            $query = CohortRequest::with(['logs', 'permissions', 'user.sector', 'user.workgroups', 'logs.user:id,name', 'user.teams:id,name'])
                ->join('users', 'cohort_requests.user_id', '=', 'users.id')
                ->leftJoin('sectors', 'users.sector_id', '=', 'sectors.id')
                ->select('cohort_requests.*', 'users.name', 'users.organisation', 'sectors.name as sector_name')
                ->distinct()
                ->when($email, function ($query) use ($email) {
                    $query->whereHas('user', function ($query) use ($email) {
                        $query->where('email', 'LIKE', '%'.$email.'%')
                            ->orWhere('secondary_email', 'LIKE', '%'.$email.'%');
                    });
                })
                ->when($name, function ($query) use ($name) {
                    $query->whereHas('user', function ($query) use ($name) {
                        $query->where('name', 'LIKE', '%'.$name.'%');
                    });
                })
                ->when($organisation, function ($query) use ($organisation) {
                    $query->whereHas('user.teams', function ($query) use ($organisation) {
                        $query->where('name', 'LIKE', '%'.$organisation.'%');
                    });
                })
                ->when($status, function ($query) use ($status) {
                    return $query->where('request_status', '=', $status);
                })
                ->when($nhseSdeStatus, function ($query) use ($nhseSdeStatus) {
                    return $query->where('nhse_sde_request_status', '=', $nhseSdeStatus);
                })
                ->when($filterText, function ($query) use ($filterText) {
                    $query->where(function ($query) use ($filterText) {
                        $query->whereHas('user', function ($q) use ($filterText) {
                            $q->where('name', 'LIKE', '%'.$filterText.'%');
                        })
                            ->orWhereHas('user.teams', function ($q) use ($filterText) {
                                $q->where('name', 'LIKE', '%'.$filterText.'%');
                            });
                    });
                });

            foreach ($sort as $key => $value) {
                if (in_array(
                    $key,
                    [
                        'created_at',
                        'updated_at',
                        'request_status',
                        'nhse_sde_request_status',
                        'nhse_sde_requested_at',
                        'nhse_sde_self_declared_approved_at',
                        'nhse_sde_updated_at',
                    ]
                )) {
                    $query->orderBy('cohort_requests.'.$key, strtoupper($value));
                }

                if (in_array($key, ['name', 'organisation'])) {
                    $query->orderBy('users.'.$key, strtoupper($value));
                }

                if (in_array($key, ['sector'])) {
                    $query->orderBy('sectors.name', strtoupper($value));
                }
            }

            $cohortRequests = $query->paginate(Config::get('constants.per_page'), ['*'], 'page');

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request get all',
            ]);

            return response()->json(
                $cohortRequests
            );
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
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
     *
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
     *       ),
     *    ),
     *
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          )
     *       )
     *    ),
     *
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *
     *        @OA\JsonContent(
     *
     *            @OA\Property(property="message", type="string", example="unauthorized")
     *        )
     *    ),
     *
     *    @OA\Response(
     *        response=404,
     *        description="Not found response",
     *
     *        @OA\JsonContent(
     *
     *            @OA\Property(property="message", type="string", example="not found"),
     *        )
     *    )
     * )
     */
    public function show(GetCohortRequest $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $cohortRequests = CohortRequest::where('id', $id)
                ->with([
                    'user.workgroups',
                    'logs' => function ($q) {
                        $q->orderBy('id', 'desc');
                    },
                    'permissions',
                    'logs.user:id,name',
                ])
                ->first()->toArray();

            if (isset($cohortRequests['logs'])) {
                foreach ($cohortRequests['logs'] as &$log) {
                    $log['details'] = html_entity_decode($log['details']);
                }
            }

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request get '.$id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $cohortRequests,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request get '.$id,
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
     *
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *
     *       @OA\MediaType(
     *          mediaType="application/json",
     *
     *          @OA\Schema(
     *
     *             @OA\Property(property="details", type="string", example="example"),
     *          )
     *       )
     *    ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="Created",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="success"),
     *              @OA\Property(property="data", type="integer", example="100")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function store(CreateCohortRequest $request): JsonResponse
    {
        // TODO in a later PR: the logic of this needs updating:
        // - handle nhs status and datestamps
        // - handle logic of when this should create or block creation
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $user = User::where(['id' => $jwtUser['id']])->first();
            if (! $user->name || strlen(trim($user->name)) === 0) {
                throw new Exception('The user name not found!');
            }

            $id = 0;
            $cohortRequest = null;
            $notAllowUpdateRequest = ['PENDING', 'APPROVED', 'BANNED', 'SUSPENDED'];

            $checkRequestByUserId = CohortRequest::where([
                'user_id' => (int) $jwtUser['id'],
            ])->first();

            // keep just one request by user id
            if ($checkRequestByUserId && in_array(strtoupper($checkRequestByUserId['request_status']), $notAllowUpdateRequest)) {
                throw new Exception('A cohort request already exists or the status of the request does not allow updating.');
            } else {
                $id = $checkRequestByUserId ? $checkRequestByUserId->id : 0;
            }

            if ($id) {
                $cohortRequest = (object) [
                    'id' => CohortRequest::where('id', $id)->update([
                        'user_id' => (int) $jwtUser['id'],
                        'request_status' => 'PENDING',
                        'request_expire_at' => null,
                        'created_at' => Carbon::today()->toDateTimeString(),
                    ]),
                ];
                CohortRequestHasPermission::where('cohort_request_id', $id)->delete();
            } else {
                $cohortRequest = CohortRequest::create([
                    'user_id' => (int) $jwtUser['id'],
                    'request_status' => 'PENDING',
                    'created_at' => Carbon::now(),
                ]);
            }

            $cohortRequestLog = CohortRequestLog::create([
                'user_id' => (int) $jwtUser['id'],
                'details' => $input['details'],
                'request_status' => 'PENDING',
                'nhse_sde_request_status' => $id ? CohortRequest::where('id', $id)->select(['nhse_sde_request_status'])->first()['nhse_sde_request_status'] : null,
            ]);

            CohortRequestHasLog::create([
                'cohort_request_id' => $id ?: $cohortRequest->id,
                'cohort_request_log_id' => $cohortRequestLog->id,
            ]);

            // send email notification
            $this->sendEmail($cohortRequest->id);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request '.($id ?: $cohortRequest->id).' created',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_CREATED.message'),
                'data' => $id ?: $cohortRequest->id,
            ], Config::get('statuscodes.STATUS_CREATED.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
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
     *
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
     *       )
     *    ),
     *
     *    @OA\RequestBody(
     *       required=true,
     *       description="Pass user credentials",
     *
     *       @OA\MediaType(
     *          mediaType="application/json",
     *
     *          @OA\Schema(
     *
     *             @OA\Property(property="request_status", type="string", example="APPROVED"),
     *             @OA\Property(property="details", type="string", example="example"),
     *          )
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             example="[]",
     *
     *             @OA\Items(
     *                type="array",
     *                @OA\Items()
     *             )
     *          )
     *       )
     *    ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=400,
     *          description="Error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="bad request"),
     *          )
     *      )
     * )
     */
    public function update(UpdateCohortRequest $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $requestStatus = strtoupper(trim($input['request_status']));
            $nhseSdeRequestStatus = strtoupper(trim($input['nhse_sde_request_status']));

            $currCohortRequest = CohortRequest::where('id', $id)->first();
            $currRequestStatus = strtoupper(trim($currCohortRequest['request_status']));
            $currNhseSdeRequestStatus = strtoupper(trim($currCohortRequest['nhse_sde_request_status']));

            $cohortRequestLog = new CohortRequestLog([
                'user_id' => (int) $jwtUser['id'],
                'details' => $input['details'],
                'request_status' => $requestStatus,
                'nhse_sde_request_status' => $nhseSdeRequestStatus,
            ]);
            $cohortRequestLog->save();

            CohortRequestHasLog::create([
                'cohort_request_id' => $id,
                'cohort_request_log_id' => $cohortRequestLog->getKey(),
            ]);

            // APPROVED / BANNED / SUSPENDED
            // PENDING - initial state
            // EXPIRED - must be an update using the chron
            $requestBeingApproved = ($currRequestStatus !== $requestStatus) && ($requestStatus === 'APPROVED');
            $requestExpireAt = $requestBeingApproved ? Carbon::now()->addDays(Config::get('cohort.cohort_access_expiry_time_in_days')) : null;
            $nhseSdeRequestBeingApproved = ($currNhseSdeRequestStatus !== $nhseSdeRequestStatus) && ($nhseSdeRequestStatus === 'APPROVED');
            $nhseSdeRequestExpireAt = $nhseSdeRequestBeingApproved ? Carbon::now()->addDays(Config::get('cohort.cohort_nhse_sde_access_expiry_time_in_days')) : null;

            if ($currRequestStatus !== $requestStatus || $currNhseSdeRequestStatus !== $nhseSdeRequestStatus) {
                CohortRequest::where('id', $id)->update([
                    'request_status' => $requestStatus,
                    'nhse_sde_request_status' => $nhseSdeRequestStatus,
                    ...(($currRequestStatus !== $requestStatus) ? ['request_expire_at' => $requestExpireAt] : []),
                    ...(($currNhseSdeRequestStatus !== $nhseSdeRequestStatus) ? ['nhse_sde_request_expire_at' => $nhseSdeRequestExpireAt] : []),
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
                    throw new Exception('A cohort request status received not accepted.');
                    break;
            }

            if (
                Feature::active('CohortDiscoveryService')
                && isset($input['workgroup_ids'])
            ) {
                //only can update workgroups once the request has been made
                // - user would create a request
                // - the admin updates to add workgroups
                $workgroupIds = $input['workgroup_ids'];
                if (! is_null($workgroupIds)) {
                    $userId = $currCohortRequest->user_id;

                    $existingIds = UserHasWorkgroup::where('user_id', $userId)
                        ->pluck('workgroup_id')
                        ->toArray();

                    UserHasWorkgroup::where('user_id', $userId)
                        ->whereNotIn('workgroup_id', $workgroupIds)
                        ->delete();

                    $toAdd = array_values(array_diff($workgroupIds, $existingIds));
                    foreach ($toAdd as $workgroupId) {
                        UserHasWorkgroup::create([
                            'user_id' => $userId,
                            'workgroup_id' => $workgroupId,
                        ]);
                    }
                }
            }

            // TODO: only send an email if there's a change.
            // - note: we might want to add what workgroups they're in to the email?
            $this->sendEmail($id);
            $this->updateOrCreateContact((int) $jwtUser['id']);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request '.$id.' updated',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => CohortRequest::where('id', $id)->first(),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request '.$id.' updated',
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
     *
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
     *    ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function destroy(DeleteCohortRequest $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $cohortRequest = CohortRequest::withTrashed()->findOrFail($id);
            $cohortRequest->update(['accept_declaration' => false]);
            $cohortRequest->delete();

            CohortRequestHasPermission::where('cohort_request_id', $id)->delete();

            $this->updateOrCreateContact($id);

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request '.$id.' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
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
     *
     *    @OA\Parameter(
     *       name="request_status",
     *       in="query",
     *       description="filter by multiple statuses",
     *       example="pending,approved",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by multiple statuses",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="organisation",
     *       in="query",
     *       description="filter by multiple organisation names",
     *       example="Org%201,Organisation%20B",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by multiple organisation names",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="from",
     *       in="query",
     *       required=true,
     *       description="filter by date range - earliest date",
     *       example="2022-12-31",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by date range - earliest date",
     *       ),
     *    ),
     *
     *    @OA\Parameter(
     *       name="to",
     *       in="query",
     *       required=true,
     *       description="filter by date range - latest date",
     *       example="2022-12-31",
     *
     *       @OA\Schema(
     *          type="string",
     *          description="filter by date range - latest date",
     *       ),
     *    ),
     *
     *    @OA\Response(
     *       response=200,
     *       description="CSV file",
     *
     *       @OA\MediaType(
     *          mediaType="text/csv",
     *
     *          @OA\Schema(
     *             type="string",
     *             example="""User ID"",Name,""Email address"",Organisation,Status,""Date Requested"",""Date Actioned""\n13,""Jackson Graham"",wmoen@example.com,""UK Health"",PENDING,""2023-09-17 13:31:25"",""2023-11-17 16:02:36""",
     *          )
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="unauthorized")
     *       )
     *    )
     * )
     */
    public function export(Request $request): StreamedResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $query = CohortRequest::with(['user', 'logs', 'logs.user', 'user.sector']);

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

            // filter by nhse_sde_request_status. Convert to uppercase for comparison.
            if ($request->has('nhse_sde_request_status')) {
                $nhseSdeRequestStatusArray = explode(',', $request->query('nhse_sde_request_status', ''));
                $nhseSdeRequestStatusArrayUpper = array_map('strtoupper', $nhseSdeRequestStatusArray);
                $query->filterByMultiNhseSdeRequestStatus($nhseSdeRequestStatusArrayUpper);
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
                        'Secondary Email address',
                        'Sector',
                        'Organisation',
                        'Bio',
                        'Domain',
                        'Link',
                        'OrcId',
                        'Profile Updated At',
                        'Status',
                        'Access To Environment',
                        'Date Requested',
                        'Date Actioned',
                        'Live',
                        'NHSE SDE Request Status',
                        'First Clicked Through To NHSE SDE Website',
                        'Declared NHS SDE Approval At',
                        'NHSE SDE Expires At',
                        'NHSE SDE Updated At',
                    ];

                    // Add CSV headers
                    fputcsv($handle, $headerRow);

                    // add the given number of rows to the file.
                    foreach ($result as $rowDetails) {
                        if (! is_null($rowDetails['user'])) {
                            $row = [
                                (string) $rowDetails['user']['id'],
                                (string) $rowDetails['user']['name'],
                                (string) $rowDetails['user']['email'],
                                (string) $rowDetails['user']['secondary_email'],
                                (string) ($rowDetails['user']['sector']['name'] ?? 'N/A'),
                                (string) $rowDetails['user']['organisation'],
                                (string) $rowDetails['user']['bio'],
                                (string) $rowDetails['user']['domain'],
                                (string) $rowDetails['user']['link'],
                                (string) $rowDetails['user']['orcid'],
                                (string) $rowDetails['user']['updated_at'],
                                (string) $rowDetails['request_status'],
                                (string) $rowDetails['access_to_env'],
                                (string) $rowDetails['created_at'],
                                (string) $rowDetails['updated_at'],
                                (string) $rowDetails['accept_declaration'],
                                (string) $rowDetails['nhse_sde_request_status'] ?? '',
                                (string) $rowDetails['nhse_sde_requested_at'] ?? '',
                                (string) $rowDetails['nhse_sde_self_declared_approved_at'] ?? '',
                                (string) $rowDetails['nhse_sde_request_expire_at'] ?? '',
                                (string) $rowDetails['nhse_sde_updated_at'] ?? '',
                            ];
                            fputcsv($handle, $row);
                        }
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
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXPORT',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request exported',
            ]);

            return $response;
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
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
     *
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
     *    ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function assignAdminPermission(AssignAdminCohortRequest $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $permissionName = 'SYSTEM_ADMIN';
            $perms = Permission::where('name', $permissionName)->first();
            if (! $perms) {
                throw new Exception($permissionName.' permission not found!');
            }

            // assign role
            CohortRequestHasPermission::create([
                'cohort_request_id' => $id,
                'permission_id' => $perms->id,
            ]);

            // send email
            $this->sendEmail($id, 'assign');

            // update HubSpot
            $this->updateOrCreateContact($id);

            // Audit log
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'CREATE',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request assign admin permission for id '.$id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
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
     *
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       description="cohort request id",
     *       required=true,
     *       example="1",
     *
     *       @OA\Schema(
     *          type="integer",
     *          description="cohort request id",
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response="200",
     *       description="Success response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="Resource deleted successfully."),
     *       )
     *    ),
     *
     *    @OA\Response(
     *       response=404,
     *       description="Error response",
     *
     *       @OA\JsonContent(
     *
     *          @OA\Property(property="message", type="string", example="Resource not found"),
     *       )
     *    ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="unauthorized")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=500,
     *          description="Error",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="error"),
     *          )
     *      )
     * )
     */
    public function removeAdminPermission(RemoveAdminCohortRequest $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        try {
            $permissionName = 'SYSTEM_ADMIN';
            $perms = Permission::where('name', $permissionName)->first();
            if (! $perms) {
                throw new Exception($permissionName.' permission not found!');
            }

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
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'DELETE',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request remove admin permission for id '.$id,
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *    path="/api/v1/cohort_requests/access/cohort-discovery",
     *    operationId="access_cohort_requests_cohort_discovery",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@checkAccessCohortDiscovery",
     *    description="Access cohort discovery by jwt",
     *    security={{"bearerAuth":{}}},
     *
     *    @OA\Response(
     *      response=200,
     *      description="Returns redirect URL for Cohort Discovery",
     *
     *      @OA\JsonContent(
     *
     *        @OA\Property(
     *          property="data",
     *          type="object",
     *          @OA\Property(property="redirect_url", type="string")
     *        )
     *      )
     *    ),
     *
     *    @OA\Response(
     *      response=500,
     *      description="Error",
     *
     *      @OA\JsonContent(
     *
     *        @OA\Property(property="message", type="string")
     *      )
     *    )
     * )
     */
    public function checkAccessCohortDiscoveryService(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $input['jwt_user'];
        $id = is_array($jwtUser) ? ($jwtUser['id'] ?? null) : null;
        $user = User::find($id);

        $guard = $this->guardAccessOrReturnResponse($request, __FUNCTION__);
        if ($guard instanceof JsonResponse) {
            return $guard;
        }

        // fail if either the user feature flag or global feature flag is turned off
        if (! (Feature::for($user)->active('CohortDiscoveryService') || Feature::active('CohortDiscoveryService'))) {
            return response()->json([
                'message' => 'Cohort Discovery is not enabled',
            ], Config::get('statuscodes.STATUS_NOT_IMPLEMENTED.code'));
        }

        $cohortDiscoveryUrl = $this->buildCohortDiscoveryRedirectUrl();

        return response()->json([
            'data' => [
                'redirect_url' => $cohortDiscoveryUrl,
            ],
        ], Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * @OA\Get(
     *    path="/api/v1/cohort_requests/access/rquest",
     *    operationId="access_cohort_requests_rquest",
     *    tags={"Cohort Requests"},
     *    summary="CohortRequestController@checkAccessRquest",
     *    description="Access RQUEST by jwt",
     *    security={{"bearerAuth":{}}},
     *
     *    @OA\Response(
     *      response=200,
     *      description="Returns redirect URL for RQUEST",
     *
     *      @OA\JsonContent(
     *
     *        @OA\Property(
     *          property="data",
     *          type="object",
     *          @OA\Property(property="redirect_url", type="string")
     *        )
     *      )
     *    ),
     *
     *    @OA\Response(
     *      response=500,
     *      description="Error",
     *
     *      @OA\JsonContent(
     *
     *        @OA\Property(property="message", type="string")
     *      )
     *    )
     * )
     */
    public function checkAccessRquest(Request $request): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $input['jwt_user'];
        $id = is_array($jwtUser) ? ($jwtUser['id'] ?? null) : null;
        $user = User::find($id);

        $guard = $this->guardAccessOrReturnResponse($request, __FUNCTION__);
        if ($guard instanceof JsonResponse) {
            return $guard;
        }

        if (! (Feature::for($user)->active('RQuest') || Feature::active('RQuest'))) {
            return response()->json([
                'message' => 'RQUEST is not enabled',
            ], Config::get('statuscodes.STATUS_NOT_IMPLEMENTED.code'));
        }

        $rquestUrl = Config::get('services.rquest.init_url');

        return response()->json([
            'data' => [
                'redirect_url' => $rquestUrl,
            ],
        ], Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * Shared guard: validates JWT user, approved request, permissions, user+email,
     * clears oauth user, sets session, logs auditor.
     *
     * Returns JsonResponse on “unauthorized-ish” cases (as your current code does),
     * otherwise returns an array context.
     *
     * @return \Illuminate\Http\JsonResponse|array{userId:int}
     */
    private function guardAccessOrReturnResponse(Request $request, string $actionName)
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : ['id' => null];

        if (! array_key_exists('id', $jwtUser)) {
            throw new Exception('Unauthorized');
        }

        try {
            $userId = (int) $jwtUser['id'];

            $checkingCohortRequest = CohortRequest::where([
                'user_id' => $userId,
                'request_status' => 'APPROVED',
            ])->first();

            if (! $checkingCohortRequest) {
                return response()->json([
                    'message' => 'Unauthorized for access :: The request is not approved',
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            $checkingCohortPerms = CohortRequestHasPermission::where([
                'cohort_request_id' => $checkingCohortRequest->id,
            ])->count();

            if (! $checkingCohortPerms) {
                return response()->json([
                    'message' => 'Unauthorized for access :: There are not enough permissions allocated for the cohort request',
                ], Config::get('statuscodes.STATUS_OK.code'));
            }

            $user = User::where(['id' => $userId])->first();
            if (! $user) {
                throw new Exception('Unauthorized for access :: The user not found');
            }

            $email = ($user->provider === 'open-athens' || $user->preferred_email === 'secondary')
                ? $user->secondary_email
                : $user->email;

            if (! $email || strlen(trim($email)) === 0) {
                throw new Exception('Unauthorized for access :: The user email not found');
            }
            if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
                throw new Exception('Unauthorized for access :: The user email is not valid');
            }

            // oidc/session setup (shared)
            OauthUser::where('user_id', $userId)->delete();
            session(['cr_uid' => $userId]);

            Auditor::log([
                'user_id' => $userId,
                'action_type' => 'GET',
                'action_name' => class_basename($this).'@'.$actionName,
                'description' => 'Access request for user',
            ]);

            return ['userId' => $userId];
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.$actionName,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Cohort Request check access :: '.$e->getMessage());
        }
    }

    private function buildCohortDiscoveryRedirectUrl(): string
    {
        $cohortServiceAccount = User::where([
            'email' => Config::get('services.cohort_discovery_service.service_account'),
            'provider' => 'service',
        ])->first();

        if (! $cohortServiceAccount) {
            throw new Exception('Cannot find cohort service account');
        }

        $cohortClient = OauthClient::where([
            'user_id' => $cohortServiceAccount->id,
        ])->first();

        if (! $cohortClient) {
            throw new Exception('Cannot find cohort service oauth client');
        }

        return config('app.url').
            '/oauth2/authorize?response_type=code'.
            "&client_id={$cohortClient->id}".
            '&scope=openid email profile rquestroles cohort_discovery_roles'.
            "&redirect_uri={$cohortClient->redirect}";
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
            if (! $admin) {
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
                '[[CURRENT_YEAR]]' => date('Y'),
                '[[USER_EMAIL]]' => $userEmail,
            ];

            if ($template) {
                SendEmailJob::dispatch($to, $template, $replacements);
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Cohort Request send email :: '.$e->getMessage());
        }
    }

    /* @OA\Get(
        *    path="/api/v1/cohort_requests/user/{id}",
        *    operationId="fetch_cohort_requests_by_user",
        *    tags={"Cohort Requests"},
        *    summary="CohortRequestController@byUser",
        *    description="Returns cohort request for given user ID",
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
        *         @OA\Items(type="object",
        *           @OA\Property(property="id", type="integer", example="123"),
        *           @OA\Property(property="user_id", type="integer", example="1"),
        *           @OA\Property(property="request_status", type="string", example="PENDING"),
        *           @OA\Property(property="cohort_status", type="boolean", example="0"),
        *           @OA\Property(property="request_expire_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="accept_declaration", type="boolean", example="0"),
        *         ),
        *       ),
        *    ),
        * )
        */
    public function byUser(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        // Check that the user is asking only for their own record.
        if (! ($jwtUser['id'] === $id)) {
            throw new UnauthorizedException();
        }

        try {
            $cohortRequest = CohortRequest::where('user_id', (int) $id)->first();

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request get by user '.$id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $cohortRequest,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /* @OA\Post(
        *    path="/api/v1/cohort_requests/user/{id}/request_nhse_access",
        *    operationId="post_nhse_access_cohort_request_by_user",
        *    tags={"Cohort Requests"},
        *    summary="CohortRequestController@requestNhseAccess",
        *    description="Indicates the user has begun the NHSE SDE access process by contacting NHS via the Gateway button",
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
        *         @OA\Items(type="object",
        *           @OA\Property(property="id", type="integer", example="123"),
        *           @OA\Property(property="user_id", type="integer", example="1"),
        *           @OA\Property(property="request_status", type="string", example="PENDING"),
        *           @OA\Property(property="cohort_status", type="boolean", example="0"),
        *           @OA\Property(property="request_expire_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="accept_declaration", type="boolean", example="0"),
        *         ),
        *       ),
        *    ),
        * )
        */
    public function requestNhseAccess(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        // Check that the user is asking only for their own record.
        if (! ($jwtUser['id'] === $id)) {
            throw new UnauthorizedException();
        }

        try {
            $cohortRequest = CohortRequest::where('user_id', (int) $id)->first();
            if (! $cohortRequest) {
                $cohortRequest = CohortRequest::create([
                    'user_id' => (int) $jwtUser['id'],
                    'request_status' => null,
                    'nhse_sde_request_status' => 'IN PROCESS',
                    'created_at' => Carbon::now(),
                    'nhse_sde_requested_at' => Carbon::now(),
                    'nhse_sde_updated_at' => Carbon::now(),
                ]);

                $cohortRequestLog = CohortRequestLog::create([
                    'user_id' => (int) $jwtUser['id'],
                    'details' => 'Clicked on "Request access to NHS SDE Network datasets" button',
                    'request_status' => null,
                    'nhse_sde_request_status' => 'IN PROCESS',
                ]);

                CohortRequestHasLog::create([
                    'cohort_request_id' => $cohortRequest->id,
                    'cohort_request_log_id' => $cohortRequestLog->id,
                ]);
            } else {
                $currNhseSdeRequestStatus = $cohortRequest->nhse_sde_request_status;

                if (
                    is_null($currNhseSdeRequestStatus)
                    || $currNhseSdeRequestStatus === 'REJECTED'
                    || $currNhseSdeRequestStatus === 'EXPIRED'
                ) {
                    $cohortRequest->update([
                        'nhse_sde_request_status' => 'IN PROCESS',
                        'nhse_sde_requested_at' => Carbon::now(),
                        'nhse_sde_updated_at' => Carbon::now(),
                    ]);

                    $cohortRequestLog = CohortRequestLog::create([
                        'user_id' => (int) $jwtUser['id'],
                        'details' => 'Clicked on "Request access to NHS SDE Network datasets" button',
                        'request_status' => $cohortRequest->request_status,
                        'nhse_sde_request_status' => 'IN PROCESS',
                    ]);

                    CohortRequestHasLog::create([
                        'cohort_request_id' => $cohortRequest->id,
                        'cohort_request_log_id' => $cohortRequestLog->id,
                    ]);
                }
            }

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request request NHSE SDE access by user '.$id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $cohortRequest,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }

    /* @OA\Post(
        *    path="/api/v1/cohort_requests/user/{id}/indicate_nhse_access",
        *    operationId="post_indicate_nhse_access_cohort_request_by_user",
        *    tags={"Cohort Requests"},
        *    summary="CohortRequestController@indicateNhseAccess",
        *    description="Indicates the user has indicated they have been granted NHSE SDE access",
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
        *         @OA\Items(type="object",
        *           @OA\Property(property="id", type="integer", example="123"),
        *           @OA\Property(property="user_id", type="integer", example="1"),
        *           @OA\Property(property="request_status", type="string", example="PENDING"),
        *           @OA\Property(property="cohort_status", type="boolean", example="0"),
        *           @OA\Property(property="request_expire_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="created_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="updated_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="deleted_at", type="datetime", example="2023-04-03 12:00:00"),
        *           @OA\Property(property="accept_declaration", type="boolean", example="0"),
        *         ),
        *       ),
        *    ),
        * )
        */
    public function indicateNhseAccess(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = array_key_exists('jwt_user', $input) ? $input['jwt_user'] : [];

        // Check that the user is asking only for their own record.
        if (! ($jwtUser['id'] === $id)) {
            throw new UnauthorizedException();
        }

        try {
            $cohortRequest = CohortRequest::where('user_id', (int) $id)->first();
            if (! $cohortRequest) {
                $cohortRequest = CohortRequest::create([
                    'user_id' => (int) $jwtUser['id'],
                    'request_status' => null,
                    'nhse_sde_request_status' => 'APPROVAL REQUESTED',
                    'created_at' => Carbon::now(),
                    'nhse_sde_self_declared_approved_at' => Carbon::now(),
                    'nhse_sde_updated_at' => Carbon::now(),
                ]);

                $cohortRequestLog = CohortRequestLog::create([
                    'user_id' => (int) $jwtUser['id'],
                    'details' => 'Clicked on "I have been approved by the NHS SDE" button',
                    'request_status' => null,
                    'nhse_sde_request_status' => 'APPROVAL REQUESTED',
                ]);

                CohortRequestHasLog::create([
                    'cohort_request_id' => $cohortRequest->id,
                    'cohort_request_log_id' => $cohortRequestLog->id,
                ]);
                // TODO: send a confirmation email to user
            } else {
                $currNhseSdeRequestStatus = $cohortRequest->nhse_sde_request_status;

                if (
                    is_null($currNhseSdeRequestStatus)
                    || $currNhseSdeRequestStatus === 'IN PROCESS'
                    || $currNhseSdeRequestStatus === 'REJECTED'
                    || $currNhseSdeRequestStatus === 'EXPIRED'
                ) {
                    $cohortRequest->update([
                        'nhse_sde_request_status' => 'APPROVAL REQUESTED',
                        'nhse_sde_self_declared_approved_at' => Carbon::now(),
                        'nhse_sde_updated_at' => Carbon::now(),
                    ]);

                    $cohortRequestLog = CohortRequestLog::create([
                        'user_id' => (int) $jwtUser['id'],
                        'details' => 'Clicked on "I have been approved by the NHS SDE" button',
                        'request_status' => $cohortRequest->request_status,
                        'nhse_sde_request_status' => 'APPROVAL REQUESTED',
                    ]);

                    CohortRequestHasLog::create([
                        'cohort_request_id' => $cohortRequest->id,
                        'cohort_request_log_id' => $cohortRequestLog->id,
                    ]);

                    // TODO: send a confirmation email to user
                }
            }

            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'GET',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => 'Cohort Request indicate NHSE SDE access by user '.$id,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $cohortRequest,
            ], 200);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => (int) $jwtUser['id'],
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this).'@'.__FUNCTION__,
                'description' => $e->getMessage(),
            ]);
            throw new Exception($e->getMessage());
        }
    }
}
