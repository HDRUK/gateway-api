<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\V3\TeamDashboard\GetTeamDashboard;
use App\Http\Traits\Responses;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Collection;
use App\Models\Publication;
use App\Services\V3\TeamDashboardService;
use Illuminate\Http\Request;

class TeamDashboardController extends Controller
{
    use Responses;

    public function __construct(
        private readonly TeamDashboardService $teamDashboardService,
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dashboard/{entity}/count",
     *     operationId="fetch_entities_count_v3",
     *     tags={"TeamDashboard"},
     *     summary="TeamDashboardController@entityCount",
     *     description="Get count of a specific entity for a team",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="entity",
     *         in="path",
     *         required=true,
     *         description="Entity type to count",
     *         @OA\Schema(type="string", enum={"datasets", "datauses", "tools", "collections"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=1),
     *                 @OA\Property(property="total_by_interval", type="integer", example=0)
     *             )
     *         )
     *     )
     * )
     */
    public function entityCount(GetTeamDashboard $request, $id, $entity)
    {
        $startDate = $request->query('startDate') ?? null;
        $endDate = $request->query('endDate') ?? null;

        if ($startDate && $endDate && $startDate > $endDate) {
            return $this->errorResponse('startDate must be less than or equal to endDate');
        }

        $response = [];

        switch ($entity) {
            case 'datasets':
                $response = $this->teamDashboardService->getCount(Dataset::class, 'active_date', $id, $startDate, $endDate);
                break;
            case 'datauses':
                $response = $this->teamDashboardService->getCount(Dur::class, 'active_date', $id, $startDate, $endDate);
                break;
            case 'tools':
                $response = $this->teamDashboardService->getCount(Tool::class, 'active_date', $id, $startDate, $endDate);
                break;
            case 'collections':
                $response = $this->teamDashboardService->getCount(Collection::class, 'active_date', $id, $startDate, $endDate);
                break;
            case 'publications':
                $response = $this->teamDashboardService->getCount(Publication::class, 'active_date', $id, $startDate, $endDate);
                break;
        }

        return $this->okResponse($response);
    }

    // GET /api/v3/teams/[id]/dashboard/360/datasets/views
    public function datasetViews(Request $request, $id)
    {
    }

    // GET /api/v3/teams/[id]/dashboard/top/datasets/views
    public function topDatasetViews(Request $request, $id)
    {
    }

    // GET /api/v3/teams/[id]/dashboard/collections/views
    public function collectionViews(Request $request, $id)
    {
    }

    // GET /api/v3/teams/[id]/dashboard/datacustodians/views
    public function datacustodianViews(Request $request, $id)
    {
    }

    // GET /api/v3/teams/[id]/dashboard/generalenquires/count
    public function generalEnquiresCount(Request $request, $id)
    {
    }

    // GET /api/v3/teams/[id]/dashboard/fesabilityenquires/count
    public function fesabilityEnquiresCount(Request $request, $id)
    {
    }

    // GET /api/v3/teams/[id]/dashboard/dataaccessrequests/count
    public function dataaccessrequestsCount(Request $request, $id)
    {
    }
}
