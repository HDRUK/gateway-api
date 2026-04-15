<?php

namespace App\Http\Controllers\Api\V3;

use App\Exports\DataAccessApplicationTimelineCsv;
use App\Exports\DataAccessDashboardCsv;
use App\Exports\DataAccessRequiredActionsCsv;
use App\Http\Controllers\Controller;
use App\Http\Traits\Responses;
use App\Services\V3\DataAccessDashboardService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DataAccessDashboardController extends Controller
{
    use Responses;

    public function __construct(
        private readonly DataAccessDashboardService $dataAccessDashboardService,
    ) {
    }

    // figma - my applications
    public function getMyApplications(Request $request, int $id)
    {
        $response = $this->dataAccessDashboardService->myApplications($id);
        return $this->okResponse($response);
    }

    // figma - current status
    public function getApplicationStatus(Request $request, int $id)
    {
        $response = $this->dataAccessDashboardService->statusApplications($id);
        return $this->okResponse($response);
    }

    // figma - Average Time to Approval
    public function getAverageTimeToApproval(Request $request, int $id)
    {
        $response = $this->dataAccessDashboardService->averageTimeToApproval($id);
        return $this->okResponse($response);
    }

    //figma - Messages and Actions
    public function getRequiredActions(Request $request, int $id)
    {
        $response = $this->dataAccessDashboardService->requiredActions($id);
        return $this->okResponse($response);
    }

    // figma - Application Timeline
    public function getApplicationTimeline(Request $request, int $id)
    {
        $response = $this->dataAccessDashboardService->applicationTimeline($id);
        return $this->okResponse($response);
    }

    // figma - export export
    public function exportDashboardCsv(Request $request, int $id)
    {
        $myApplications = $this->dataAccessDashboardService->myApplications($id);
        $averageTimeToApproval = $this->dataAccessDashboardService->averageTimeToApproval($id);
        $requiredActions = $this->dataAccessDashboardService->requiredActions($id);
        $applicationIimeline = $this->dataAccessDashboardService->applicationTimeline($id);

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

    // figma - export export
    public function exportDashboardTimelineCsv(Request $request, int $id)
    {
        $applicationIimeline = $this->dataAccessDashboardService->applicationTimeline($id);

        return Excel::download(
            new DataAccessApplicationTimelineCsv(
                $applicationIimeline,
            ),
            'dashboard.csv',
        );
    }

    // figma - export messages & required actions
    public function exportRequiredActionsCsv(Request $request, int $id)
    {
        $requiredActions = $this->dataAccessDashboardService->requiredActions($id);

        dd($requiredActions);

        return Excel::download(
            new DataAccessRequiredActionsCsv(
                $requiredActions,
            ),
            'dashboard.csv',
        );
    }
}
