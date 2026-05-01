<?php

namespace App\Http\Controllers\Api\V3;

use App\Exports\DashboardCsvExport;
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
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

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
     *     @OA\Parameter(
     *         name="startDate",
     *         in="query",
     *         required=false,
     *         description="Start date for the reporting interval (Y-m-d). Defaults to one year ago.",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="endDate",
     *         in="query",
     *         required=false,
     *         description="End date for the reporting interval (Y-m-d). Defaults to today.",
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
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
        $dateRange = $this->resolveDateRange($request);

        if ($dateRange instanceof JsonResponse) {
            return $dateRange;
        }

        [$startDate, $endDate] = $dateRange;

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
     *     @OA\Parameter(
     *         name="startDate",
     *         in="query",
     *         required=false,
     *         description="Start date for the reporting interval (Y-m-d). Defaults to one year ago.",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="endDate",
     *         in="query",
     *         required=false,
     *         description="End date for the reporting interval (Y-m-d). Defaults to today.",
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
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
        $dateRange = $this->resolveDateRange($request);

        if ($dateRange instanceof JsonResponse) {
            return $dateRange;
        }

        [$startDate, $endDate] = $dateRange;

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
     *     @OA\Parameter(
     *         name="startDate",
     *         in="query",
     *         required=false,
     *         description="Start date for the reporting interval (Y-m-d). Defaults to one year ago.",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="endDate",
     *         in="query",
     *         required=false,
     *         description="End date for the reporting interval (Y-m-d). Defaults to today.",
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
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
        $dateRange = $this->resolveDateRange($request);

        if ($dateRange instanceof JsonResponse) {
            return $dateRange;
        }

        [$startDate, $endDate] = $dateRange;

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
     *     @OA\Parameter(
     *         name="startDate",
     *         in="query",
     *         required=false,
     *         description="Start date for the reporting interval (Y-m-d). Defaults to one year ago.",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="endDate",
     *         in="query",
     *         required=false,
     *         description="End date for the reporting interval (Y-m-d). Defaults to today.",
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
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
        $dateRange = $this->resolveDateRange($request);

        if ($dateRange instanceof JsonResponse) {
            return $dateRange;
        }

        [$startDate, $endDate] = $dateRange;

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
     *     @OA\Parameter(
     *         name="startDate",
     *         in="query",
     *         required=false,
     *         description="Start date for the reporting interval (Y-m-d). Defaults to one year ago.",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="endDate",
     *         in="query",
     *         required=false,
     *         description="End date for the reporting interval (Y-m-d). Defaults to today.",
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
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
        $dateRange = $this->resolveDateRange($request);

        if ($dateRange instanceof JsonResponse) {
            return $dateRange;
        }

        [$startDate, $endDate] = $dateRange;

        $response = $this->teamDashboardService->getEntityViews('data-custodian', $id, $startDate, $endDate);

        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dashboard/download/csv",
     *     operationId="fetch_dashboard_download_csv_v3",
     *     tags={"TeamDashboard"},
     *     summary="TeamDashboardController@downloadCsv",
     *     description="Download dashboard data custodian in csv format",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Team ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="startDate",
     *         in="query",
     *         required=false,
     *         description="Start date for the reporting interval (Y-m-d). Defaults to one year ago.",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="endDate",
     *         in="query",
     *         required=false,
     *         description="End date for the reporting interval (Y-m-d). Defaults to today.",
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV file download containing dashboard metrics for the team",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid team ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid argument(s)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Invalid date interval",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="error"),
     *             @OA\Property(property="data", type="string", example="startDate must be less than or equal to endDate")
     *         )
     *     )
     * )
     */
    public function downloadCsv(GetTeamDashboard $request, $id)
    {
        $dateRange = $this->resolveDateRange($request);

        if ($dateRange instanceof JsonResponse) {
            return $dateRange;
        }

        [$startDate, $endDate] = $dateRange;

        $entityDatasets = $this->teamDashboardService->getCount(Dataset::class, 'active_date', $id, $startDate, $endDate, ['status'  => Dataset::STATUS_ACTIVE]);
        $entityDataUses = $this->teamDashboardService->getCount(Dur::class, 'active_date', $id, $startDate, $endDate, ['status'  => Dur::STATUS_ACTIVE]);
        $entityTools = $this->teamDashboardService->getCount(Tool::class, 'active_date', $id, $startDate, $endDate, ['status'  => Tool::STATUS_ACTIVE]);
        $entityPublications = $this->teamDashboardService->getCount(Publication::class, 'active_date', $id, $startDate, $endDate, ['status'  => Publication::STATUS_ACTIVE]);
        $entityCollections = $this->teamDashboardService->getCount(Collection::class, 'active_date', $id, $startDate, $endDate, ['status'  => Collection::STATUS_ACTIVE]);
        $entityGeneralEnquiries = $this->teamDashboardService->getCount(EnquiryThread::class, 'created_at', $id, $startDate, $endDate, ['is_general_enquiry' => 1]);
        $entityFeasabilityEnquiries = $this->teamDashboardService->getCount(EnquiryThread::class, 'created_at', $id, $startDate, $endDate, ['is_feasibility_enquiry' => 1]);
        $entityDataAccessRequests = $this->teamDashboardService->getCount(TeamHasDataAccessApplication::class, 'created_at', $id, $startDate, $endDate, []);
        $dataset360Views = $this->teamDashboardService->getDatasetViews($id, $startDate, $endDate);
        $datasetTopViews = $this->teamDashboardService->getDatatasetViewsTop($id, $startDate, $endDate);
        $collectionViews = $this->teamDashboardService->getEntityViews('collection', $id, $startDate, $endDate);
        $dataCustodianViews = $this->teamDashboardService->getEntityViews('data-custodian', $id, $startDate, $endDate);

        return Excel::download(
            new DashboardCsvExport(
                $entityDatasets,
                $entityDataUses,
                $entityTools,
                $entityPublications,
                $entityCollections,
                $entityGeneralEnquiries,
                $entityFeasabilityEnquiries,
                $entityDataAccessRequests,
                $dataset360Views,
                $datasetTopViews,
                $collectionViews,
                $dataCustodianViews,
                $startDate,
                $endDate,
            ),
            'dashboard.csv',
        );
    }

    private function resolveDateRange(GetTeamDashboard $request): JsonResponse|array
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        if ($startDate && $endDate && $startDate > $endDate) {
            return $this->errorResponse('startDate must be less than or equal to endDate');
        }

        return [
            $startDate ?? now()->subYear()->format('Y-m-d'),
            $endDate ?? now()->format('Y-m-d'),
        ];
    }
}
