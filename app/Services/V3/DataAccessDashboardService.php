<?php

namespace App\Services\V3;

use App\Models\DataAccessApplication;
use App\Models\TeamHasDataAccessApplication;
use DB;

class DataAccessDashboardService
{
    public function myApplications(int $teamId)
    {
        $total = DB::select('
                SELECT dar_applications.submission_status, count(*) counter
                FROM dar_applications
                JOIN team_has_dar_applications on (team_has_dar_applications.team_id = ? and dar_applications.id = team_has_dar_applications.dar_application_id)
                GROUP BY dar_applications.submission_status
            ', [$teamId]);


        return $total;
    }

    public function statusApplications(int $teamId)
    {
        $response = DB::select('
            SELECT dar_applications.*
            FROM dar_applications
            JOIN team_has_dar_applications on (team_has_dar_applications.team_id = ? and dar_applications.id = team_has_dar_applications.dar_application_id)
            ORDER BY dar_applications.updated_at DESC
            LIMIT 3
        ', [$teamId]);

        return $response;
    }

    public function averageTimeToApproval(int $teamId)
    {
        $appIds = TeamHasDataAccessApplication::where('team_id', $teamId)->select('dar_application_id')->pluck('dar_application_id')->toArray();

        if (empty($appIds)) {
            return [
                'avg_diff_days' => 0,
            ];
        }

        $appIds = implode(', ', $appIds);

        $response = DB::select('
            SELECT AVG(diff_days) as avg_diff_days
            FROM (
                SELECT 
                application_id, 
                    MIN(CASE WHEN submission_status = ? THEN created_at END) as first_status_date,
                    MAX(CASE WHEN approval_status IN (?, ?) THEN created_at END) as last_status_date,
                    DATEDIFF(
                        MAX(CASE WHEN approval_status IN (?, ?) THEN created_at END),
                        MIN(CASE WHEN submission_status = ? THEN created_at END)
                    ) as diff_days
                FROM dar_application_statuses
                WHERE application_id IN (' . $appIds . ')
                GROUP BY application_id
                HAVING COUNT(CASE WHEN submission_status = ? THEN 1 END) > 0
                    AND COUNT(CASE WHEN approval_status IN (?, ?) THEN 1 END) > 0
            ) as subqry
        ', ['SUBMITTED', 'APPROVED', 'APPROVED_COMMENTS',  'APPROVED', 'APPROVED_COMMENTS', 'SUBMITTED', 'SUBMITTED', 'APPROVED', 'APPROVED_COMMENTS']);

        return $response[0];
    }

    public function requiredActions(int $teamId)
    {
        $response = DataAccessApplication::query()
                    ->with('team')
                    // ->where('')
                    ->get()
                    ->toArray();


        return $response;
    }

    public function getApplicationTimeline(int $teamId)
    {
        return $teamId;
    }
}
