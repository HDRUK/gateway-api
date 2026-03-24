<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ProjectGrantService;
use App\Http\Controllers\Controller;
use App\Exceptions\NotFoundException;

class ProjectGrantController extends Controller
{
    public function __construct(
        private readonly ProjectGrantService $projectGrantService
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
     *             type="array"
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

            return response()->json([
                'message' => Config::get('statuscodes.STATUS_OK.message'),
                'data' => $projectGrant,
            ], Config::get('statuscodes.STATUS_OK.code'));
        } catch (Exception $e) {
            Auditor::log([
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

