<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\V3\TeamDashboard\GetTeamDashboard;
use App\Http\Traits\Responses;
use App\Models\Collection;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\EnquiryThread;
use App\Models\Publication;
use App\Models\TeamHasDataAccessApplication;
use App\Models\Tool;
use App\Services\V3\TeamDashboardService;

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
     *         @OA\Schema(type="string", enum={"datasets", "datauses", "tools", "collections", "general-enquires", "fesability-enquires", "data-access-requests"})
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

        if ($startDate === null || $endDate === null) {
            $startDate = now()->subYear()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        $response = [];

        switch ($entity) {
            case 'datasets':
                $response = $this->teamDashboardService->getCount(Dataset::class, 'active_date', $id, $startDate, $endDate, ['status'  => Dataset::STATUS_ACTIVE]);
                break;
            case 'datauses':
                $response = $this->teamDashboardService->getCount(Dur::class, 'active_date', $id, $startDate, $endDate, ['status'  => Dur::STATUS_ACTIVE]);
                break;
            case 'tools':
                $response = $this->teamDashboardService->getCount(Tool::class, 'active_date', $id, $startDate, $endDate, ['status'  => Tool::STATUS_ACTIVE]);
                break;
            case 'collections':
                $response = $this->teamDashboardService->getCount(Collection::class, 'active_date', $id, $startDate, $endDate, ['status'  => Collection::STATUS_ACTIVE]);
                break;
            case 'publications':
                $response = $this->teamDashboardService->getCount(Publication::class, 'active_date', $id, $startDate, $endDate, ['status'  => Publication::STATUS_ACTIVE]);
                break;
            case 'general-enquires':
                $response = $this->teamDashboardService->getCount(EnquiryThread::class, 'created_at', $id, $startDate, $endDate, ['is_general_enquiry' => 1]);
                break;
            case 'fesability-enquires':
                $response = $this->teamDashboardService->getCount(EnquiryThread::class, 'created_at', $id, $startDate, $endDate, ['is_feasibility_enquiry' => 1]);
                break;
            case 'data-access-requests':
                $response = $this->teamDashboardService->getCount(TeamHasDataAccessApplication::class, 'created_at', $id, $startDate, $endDate, []);
                break;
            default:
                return $this->errorResponse('Invalid entity type');
        }

        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dashboard/datasets/views/360",
     *     operationId="fetch_dataset_views_360_v3",
     *     tags={"TeamDashboard"},
     *     summary="TeamDashboardController@datasetViews360",
     *     description="Get count of a datasets views 360 for a team",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="date", type="string", example="2025-04-01"),
     *                 @OA\Property(property="counter", type="integer", example=0)
     *             )
     *         )
     *     )
     * )
     */
    public function datasetViews360(GetTeamDashboard $request, $id)
    {
        $startDate = $request->query('startDate') ?? null;
        $endDate = $request->query('endDate') ?? null;

        if ($startDate && $endDate && $startDate > $endDate) {
            return $this->errorResponse('startDate must be less than or equal to endDate');
        }

        if ($startDate === null || $endDate === null) {
            $startDate = now()->subYear()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        $response = $this->teamDashboardService->getDatasetViews($id, $startDate, $endDate);

        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dashboard/datasets/views/top",
     *     operationId="fetch_dataset_views_top_v3",
     *     tags={"TeamDashboard"},
     *     summary="TeamDashboardController@datasetViewsTop",
     *     description="Get count of a datasets views top for a team",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="dataset title"),
     *                 @OA\Property(property="counter", type="integer", example=0)
     *             )
     *         )
     *     )
     * )
     */
    public function datasetViewsTop(GetTeamDashboard $request, $id)
    {
        $startDate = $request->query('startDate') ?? null;
        $endDate = $request->query('endDate') ?? null;

        if ($startDate && $endDate && $startDate > $endDate) {
            return $this->errorResponse('startDate must be less than or equal to endDate');
        }

        if ($startDate === null || $endDate === null) {
            $startDate = now()->subYear()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        $response = $this->teamDashboardService->getDatatasetViewsTop($id, $startDate, $endDate);

        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dashboard/collections/views",
     *     operationId="fetch_collections_views_v3",
     *     tags={"TeamDashboard"},
     *     summary="TeamDashboardController@collectionViews",
     *     description="Get count of a collection views for a team",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example=0),
     *         )
     *     )
     * )
     */
    public function collectionViews(GetTeamDashboard $request, $id)
    {
        $startDate = $request->query('startDate') ?? null;
        $endDate = $request->query('endDate') ?? null;

        if ($startDate && $endDate && $startDate > $endDate) {
            return $this->errorResponse('startDate must be less than or equal to endDate');
        }

        if ($startDate === null || $endDate === null) {
            $startDate = now()->subYear()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        $response = $this->teamDashboardService->getEntityViews('collection', $id, $startDate, $endDate);

        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dashboard/datacustodians/views",
     *     operationId="fetch_data_custodians_views_v3",
     *     tags={"TeamDashboard"},
     *     summary="TeamDashboardController@datacustodianViews",
     *     description="Get count of a data custodian views for a team",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="success"),
     *             @OA\Property(property="data", type="integer", example=0),
     *         )
     *     )
     * )
     */
    public function datacustodianViews(GetTeamDashboard $request, $id)
    {
        $startDate = $request->query('startDate') ?? null;
        $endDate = $request->query('endDate') ?? null;

        if ($startDate && $endDate && $startDate > $endDate) {
            return $this->errorResponse('startDate must be less than or equal to endDate');
        }

        if ($startDate === null || $endDate === null) {
            $startDate = now()->subYear()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        }

        $response = $this->teamDashboardService->getEntityViews('data-custodian', $id, $startDate, $endDate);

        return $this->okResponse($response);
    }
}
