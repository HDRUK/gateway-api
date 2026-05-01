<?php

namespace App\Http\Controllers\Api\V3;

use App\Exports\DataAccessApplicationTimelineCsv;
use App\Exports\DataAccessDashboardCsv;
use App\Exports\DataAccessRequiredActionsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\V3\DataAccessDashboard\GetTeamDataAccessDashboard;
use App\Http\Traits\Responses;
use App\Services\V3\DataAccessDashboardService;
use Maatwebsite\Excel\Facades\Excel;

class DataAccessDashboardController extends Controller
{
    use Responses;

    public function __construct(
        private readonly DataAccessDashboardService $dataAccessDashboardService,
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/count",
     *     operationId="fetch_dar_my_applications_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@getMyApplications",
     *     description="Get Dar applications for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function getMyApplications(GetTeamDataAccessDashboard $request, int $id)
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

        $response = $this->dataAccessDashboardService->myApplications($id, $startDate, $endDate);
        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/status",
     *     operationId="fetch_dar_applications_current_status_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@getApplicationStatus",
     *     description="Get Dar applications current status for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function getApplicationStatus(GetTeamDataAccessDashboard $request, int $id)
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

        $response = $this->dataAccessDashboardService->statusApplications($id, $startDate, $endDate);
        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/average-time",
     *     operationId="fetch_dar_applications_average_time_to_approval_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@getAverageTimeToApproval",
     *     description="Get Dar applications average time to approval for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function getAverageTimeToApproval(GetTeamDataAccessDashboard $request, int $id)
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

        $response = $this->dataAccessDashboardService->averageTimeToApproval($id, $startDate, $endDate);
        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/required-actions",
     *     operationId="fetch_dar_applications_required_actions_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@getRequiredActions",
     *     description="Get Dar applications required actions for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function getRequiredActions(GetTeamDataAccessDashboard $request, int $id)
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

        $response = $this->dataAccessDashboardService->requiredActions($id, $startDate, $endDate);
        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/timeline",
     *     operationId="fetch_dar_applications_application_timeline_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@getApplicationTimeline",
     *     description="Get Dar applications timeline for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function getApplicationTimeline(GetTeamDataAccessDashboard $request, int $id)
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

        $response = $this->dataAccessDashboardService->applicationTimeline($id, $startDate, $endDate);
        return $this->okResponse($response);
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/export/csv",
     *     operationId="fetch_dar_applications_dashboard_export_csv_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@exportDashboardCsv",
     *     description="Get Dar applications dashboard export csv for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function exportDashboardCsv(GetTeamDataAccessDashboard $request, int $id)
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

        $myApplications = $this->dataAccessDashboardService->myApplications($id, $startDate, $endDate);
        $averageTimeToApproval = $this->dataAccessDashboardService->averageTimeToApproval($id, $startDate, $endDate);
        $requiredActions = $this->dataAccessDashboardService->requiredActions($id, $startDate, $endDate);
        $applicationIimeline = $this->dataAccessDashboardService->applicationTimeline($id, $startDate, $endDate);

        return Excel::download(
            new DataAccessDashboardCsv(
                $myApplications,
                $averageTimeToApproval,
                $requiredActions,
                $applicationIimeline,
            ),
            'dashboard.csv',
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/timeline/export/csv",
     *     operationId="fetch_dar_applications_dashboard_timeline_export_csv_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@exportDashboardTimelineCsv",
     *     description="Get Dar applications dashboard timeline export csv for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function exportDashboardTimelineCsv(GetTeamDataAccessDashboard $request, int $id)
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

        $applicationIimeline = $this->dataAccessDashboardService->applicationTimeline($id, $startDate, $endDate);

        return Excel::download(
            new DataAccessApplicationTimelineCsv(
                $applicationIimeline,
            ),
            'dashboard.csv',
        );
    }

    // figma - export messages & required actions
    /**
     * @OA\Get(
     *     path="/api/v3/teams/{id}/dar/dashboard/required-actions/export/csv",
     *     operationId="fetch_dar_applications_dashboard_required_actions_export_csv_v3",
     *     tags={"TeamDashboard"},
     *     summary="DataAccessDashboardController@exportRequiredActionsCsv",
     *     description="Get Dar applications dashboard timeline export csv for a team",
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
     *                 type="object")
     *         )
     *     )
     * )
     */
    public function exportRequiredActionsCsv(GetTeamDataAccessDashboard $request, int $id)
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

        $requiredActions = $this->dataAccessDashboardService->requiredActions($id, $startDate, $endDate);

        return Excel::download(
            new DataAccessRequiredActionsCsv(
                $requiredActions,
            ),
            'dashboard.csv',
        );
    }
}
