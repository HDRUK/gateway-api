<?php

namespace App\Http\Controllers\Api\V1;

use Config;
use Auditor;
use Exception;
use Illuminate\Http\Request;
use App\Models\ProjectGrant;
use App\Models\ProjectGrantVersion;
use App\Models\ProjectGrantVersionHasDataset;
use Illuminate\Http\JsonResponse;
use App\Context\PartnerContext;
use App\Services\ProjectGrantService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectGrant\CreateProjectGrant;
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
        $jwtUser = $request->input('jwt_user', []);
        $currentUser = isset($jwtUser['id']) ? (int) $jwtUser['id'] : $userId;

        if (!is_null($teamId)) {
            $this->checkAccess($request->all(), $teamId, null, 'team', $request->header());
        }

        try {
            $input = $request->validated();

            $grant = ProjectGrant::create([
                'pid' => $input['pid'],
                'user_id' => $currentUser,
                'team_id' => (int) $teamId,
            ]);

            $versionNumber = array_key_exists('version', $input) ? (int) $input['version'] : 1;
            $version = ProjectGrantVersion::create([
                'project_grant_id' => $grant->id,
                'version' => $versionNumber,
                'project_grant_name' => $input['projectGrantName'],
                'lead_researcher' => $input['leadResearcher'] ?? null,
                'lead_research_institute' => $input['leadResearchInstitute'] ?? null,
                'grant_numbers' => $input['grantNumbers'] ?? null,
                'project_grant_start_date' => $input['projectGrantStartDate'] ?? null,
                'project_grant_end_date' => $input['projectGrantEndDate'] ?? null,
                'project_grant_scope' => $input['projectGrantScope'] ?? null,
            ]);

            if (!empty($input['datasets'])) {
                foreach ($input['datasets'] as $datasetId) {
                    ProjectGrantVersionHasDataset::firstOrCreate([
                        'project_grant_id' => $grant->id,
                        'dataset_id' => (int) $datasetId,
                    ]);
                }
            }

            if (!empty($input['publications'])) {
                $version->publications()->sync($input['publications']);
            }

            if (!empty($input['tools'])) {
                $version->tools()->sync($input['tools']);
            }

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

    private function getProjectGrantById(int $projectGrantId, bool $withRelated)
    {
        $projectGrant = $this->projectGrantService->findById($projectGrantId, $withRelated);

        if (!$projectGrant) {
            throw new NotFoundException();
        }

        return $projectGrant;
    }
}
