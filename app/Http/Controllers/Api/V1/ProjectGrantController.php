<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use App\Models\ProjectGrant;
use Illuminate\Http\JsonResponse;
use App\Context\PartnerContext;
use App\Services\ProjectGrantService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectGrant\CreateProjectGrant;
use App\Http\Requests\ProjectGrant\EditProjectGrant;
use App\Http\Requests\ProjectGrant\UpdateProjectGrant;
use App\Exceptions\NotFoundException;
use App\Http\Traits\CheckAccess;
use App\Http\Traits\Responses;

class ProjectGrantController extends Controller
{
    use CheckAccess;
    use Responses;

    public function __construct(
        private readonly ProjectGrantService $projectGrantService,
        private readonly PartnerContext $partnerContext
    ) {
    }

    /**
     * @OA\Get(
     *    path="/api/v1/project_grants",
     *    operationId="fetch_all_project_grants",
     *    tags={"Project Grant"},
     *    summary="ProjectGrantController@index",
     *    description="Get all project grants",
     *    @OA\Parameter(
     *       name="pid",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter by dataset pid"
     *    ),
     *    @OA\Parameter(
     *       name="version",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter by dataset version number"
     *    ),
     *    @OA\Parameter(
     *       name="projectGrantName",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="string"),
     *       description="Filter by project grant name"
     *    ),
     *    @OA\Parameter(
     *       name="user_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter by owning user id"
     *    ),
     *    @OA\Parameter(
     *       name="team_id",
     *       in="query",
     *       required=false,
     *       @OA\Schema(type="integer"),
     *       description="Filter by owning team id"
     *    ),
     *    @OA\Parameter(
     *       name="with_related",
     *       in="query",
     *       required=false,
     *       example=true,
     *       @OA\Schema(type="boolean")
     *    ),
     *    @OA\Response(
     *        response="200",
     *        description="Success response",
     *        @OA\JsonContent(
     *          @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(type="object")
     *          )
     *        )
     *    )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $pid = $request->query('pid', null);
            $version = $request->query('version') !== null ? (int) $request->query('version') : null;
            $projectGrantName = $request->query('projectGrantName', null);
            $userId = $request->query('user_id') !== null ? (int) $request->query('user_id') : null;
            $teamId = $request->query('team_id') !== null ? (int) $request->query('team_id') : null;

            $perPage = request('per_page', Config::get('constants.per_page'));
            $withRelated = $request->boolean('with_related', true);

            $sort = $request->query('sort', 'created_at:desc');
            $projectGrants = $this->projectGrantService->list(
                pid: $pid,
                version: $version,
                projectGrantName: $projectGrantName,
                userId: $userId,
                teamId: $teamId,
                withRelated: $withRelated,
                perPage: $perPage,
                sort: $sort
            );

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'ProjectGrant get all',
            ]);

            $resourceClass = $this->partnerContext->indexResourceFor(ProjectGrant::class);
            $projectGrants->through(
                fn ($projectGrant) => $resourceClass::make($projectGrant)->resolve($request)
            );

            return response()->json($projectGrants);
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
     *    path="/api/v1/project_grants/{id}",
     *    operationId="fetch_project_grant",
     *    tags={"Project Grant"},
     *    summary="ProjectGrantController@show",
     *    description="Get a single project grant",
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *    ),
     *    @OA\Parameter(
     *       name="with_related",
     *       in="query",
     *       required=false,
     *       example=true,
     *       @OA\Schema(type="boolean")
     *    ),
     *    @OA\Response(
     *        response="200",
     *        description="Success response",
     *        @OA\JsonContent(
     *          @OA\Property(property="data", type="object")
     *        )
     *    )
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $withRelated = $request->boolean('with_related', true);
            $projectGrant = $this->getProjectGrantById($id, $withRelated);

            Auditor::log([
                'action_type' => 'GET',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'ProjectGrant get ' . $id,
            ]);

            $resourceClass = $this->partnerContext->resourceFor(ProjectGrant::class);
            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $resourceClass::make($projectGrant)->resolve($request),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (NotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
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
     * @OA\Post(
     *    path="/api/v1/project_grants",
     *    operationId="create_project_grant",
     *    tags={"Project Grant"},
     *    summary="ProjectGrantController@store",
     *    description="Create a project grant (and initial version)",
     *    security={{"bearerAuth":{}}},
     *    @OA\Response(
     *        response="201",
     *        description="Created",
     *        @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="created"),
     *          @OA\Property(property="data", type="object")
     *        )
     *    )
     * )
     */
    public function store(CreateProjectGrant $request): JsonResponse
    {
        list($userId, $teamId) = $this->getAccessorUserAndTeam($request);
        $jwtUser = $request->jwtUser();
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId;

        if (!is_null($teamId)) {
            $this->checkAccess($request->all(), $teamId, null, 'team', $request->header());
        }

        try {
            $grant = $this->projectGrantService->create(
                $request->validated(),
                $currentUser,
                (int) $teamId
            );

            Auditor::log([
                'user_id' => $currentUser,
                'team_id' => $teamId,
                'action_type' => 'CREATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'ProjectGrant ' . $grant->id . ' created',
            ]);

            $withRelated = $request->boolean('with_related', true);
            $created = $this->getProjectGrantById($grant->id, $withRelated);
            $resourceClass = $this->partnerContext->resourceFor(ProjectGrant::class);

            return response()->json([
                'message' => 'created',
                'data' => $resourceClass::make($created)->resolve($request),
            ], 201);
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'team_id' => $teamId,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *    path="/api/v1/project_grants/{id}",
     *    operationId="update_project_grant",
     *    tags={"Project Grant"},
     *    summary="ProjectGrantController@update",
     *    description="Replace project grant fields and create a new version",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *    ),
     *    @OA\Response(
     *        response="200",
     *        description="Success",
     *        @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="object")
     *        )
     *    )
     * )
     */
    public function update(UpdateProjectGrant $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $request->input('jwt_user', []);
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : 0;

        try {
            $grant = ProjectGrant::where('id', $id)->first();
            if (!$grant) {
                throw new NotFoundException();
            }

            $this->checkAccess($input, $grant->team_id, null, 'team', $request->header());

            $this->projectGrantService->update($grant, $request->validated());

            Auditor::log([
                'user_id' => $currentUser,
                'team_id' => $grant->team_id,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'ProjectGrant ' . $id . ' updated',
            ]);

            $withRelated = $request->boolean('with_related', true);
            $updated = $this->getProjectGrantById($id, $withRelated);
            $resourceClass = $this->partnerContext->resourceFor(ProjectGrant::class);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $resourceClass::make($updated)->resolve($request),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (NotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Patch(
     *    path="/api/v1/project_grants/{id}",
     *    operationId="edit_project_grant",
     *    tags={"Project Grant"},
     *    summary="ProjectGrantController@edit",
     *    description="Partially update a project grant and/or its latest version",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *    ),
     *    @OA\Response(
     *        response="200",
     *        description="Success",
     *        @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success"),
     *          @OA\Property(property="data", type="object")
     *        )
     *    )
     * )
     */
    public function edit(EditProjectGrant $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $request->input('jwt_user', []);
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : 0;

        try {
            $grant = ProjectGrant::where('id', $id)->first();
            if (!$grant) {
                throw new NotFoundException();
            }

            $this->checkAccess($input, $grant->team_id, null, 'team', $request->header());

            $this->projectGrantService->edit($grant, $request->validated());

            Auditor::log([
                'user_id' => $currentUser,
                'team_id' => $grant->team_id,
                'action_type' => 'UPDATE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'ProjectGrant ' . $id . ' edited',
            ]);

            $withRelated = $request->boolean('with_related', true);
            $updated = $this->getProjectGrantById($id, $withRelated);
            $resourceClass = $this->partnerContext->resourceFor(ProjectGrant::class);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $resourceClass::make($updated)->resolve($request),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (NotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *    path="/api/v1/project_grants/{id}",
     *    operationId="delete_project_grant",
     *    tags={"Project Grant"},
     *    summary="ProjectGrantController@destroy",
     *    description="Soft delete a project grant and its versions",
     *    security={{"bearerAuth":{}}},
     *    @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       @OA\Schema(type="integer")
     *    ),
     *    @OA\Response(
     *        response="200",
     *        description="Success",
     *        @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="success")
     *        )
     *    )
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $jwtUser = $request->input('jwt_user', []);
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : 0;

        try {
            $grant = ProjectGrant::where('id', $id)->first();
            if (!$grant) {
                throw new NotFoundException();
            }

            $this->checkAccess($input, $grant->team_id, null, 'team', $request->header());

            $this->projectGrantService->delete($grant);

            Auditor::log([
                'user_id' => $currentUser,
                'team_id' => $grant->team_id,
                'action_type' => 'DELETE',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'ProjectGrant ' . $id . ' deleted',
            ]);

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (NotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (Exception $e) {
            Auditor::log([
                'user_id' => $currentUser,
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }

    private function getProjectGrantById(int $projectGrantId, bool $withRelated)
    {
        $projectGrant = $this->projectGrantService->findById($projectGrantId, $withRelated);

        if (!$projectGrant) {
            throw new NotFoundException();
        }

        return $projectGrant;
    }
}
