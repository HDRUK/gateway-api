<?php

namespace App\Services\V3;

use App\Models\BankHoliday;
use App\Models\DataAccessApplication;
use App\Models\TeamHasDataAccessApplication;
use Carbon\Carbon;
use DB;

class DataAccessDashboardService
{
    // ->paginate(Config::get('constants.per_page'), ['*'], 'page');

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

    // ??
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

        $rows = DB::select('
                SELECT 
                    application_id, 
                    MIN(CASE WHEN submission_status = ? THEN created_at END) as first_status_date,
                    MAX(CASE WHEN approval_status IN (?, ?) THEN created_at END) as last_status_date
                FROM dar_application_statuses
                WHERE application_id IN (' . $appIds . ')
                GROUP BY application_id
                HAVING COUNT(CASE WHEN submission_status = ? THEN 1 END) > 0
                    AND COUNT(CASE WHEN approval_status IN (?, ?) THEN 1 END) > 0
        ', ['SUBMITTED', 'APPROVED', 'APPROVED_COMMENTS', 'SUBMITTED', 'APPROVED', 'APPROVED_COMMENTS']);

        if (empty($rows)) {
            return [
                'avg_diff_days' => 0,
            ];
        } else {
            $workingDaysBetweenDates = function ($from, $to): int {
                $holidays = BankHoliday::query()
                    ->where([
                        'country' => 'GB',
                        'region' => 'england-and-wales',
                    ])
                    ->whereBetween('holiday_date', [
                        Carbon::parse($from)->toDateString(),
                        Carbon::parse($to)->toDateString(),
                    ])
                    ->pluck('holiday_date')
                    ->map(fn ($d) => Carbon::parse($d)->toDateString())
                    ->toArray();

                $workingDays = 0;

                $current = Carbon::parse($from);
                $end = Carbon::parse($to);

                while ($current->lte($end)) {
                    if (!$current->isWeekend() && !in_array($current->toDateString(), $holidays)) {
                        $workingDays++;
                    }

                    $current->addDay();
                }

                return $workingDays;
            };

            $diffs = collect($rows)
                ->filter(fn ($r) => $r->first_status_date && $r->last_status_date)
                ->map(function ($r) use ($workingDaysBetweenDates) {
                    $r->diff_days = $workingDaysBetweenDates($r->first_status_date, $r->last_status_date);
                    return $r;
                });

            $avg = $diffs->isNotEmpty() ? $diffs->average('diff_days') : 0;

            return [
                'avg_diff_days' => $avg,
            ];
        }
    }

    public function requiredActions(int $teamId)
    {
        $response = DataAccessApplication::query()
                    ->with([
                        'team',
                        'reviews.comments',
                        'user',
                        'states'
                    ])
                    ->whereHas('team', fn ($q) => $q->where('teams.id', $teamId))
                    ->whereHas('reviews', fn ($q) => $q->whereHas('comments'))
                    ->get()
                    ->toArray();

        return $response;
    }

    public function applicationTimeline(int $teamId)
    {
        $response = DataAccessApplication::query()
                    ->with([
                        'team',
                        'states'
                    ])
                    ->whereHas('team', fn ($q) => $q->where('teams.id', $teamId))
                    ->whereHas('reviews', fn ($q) => $q->whereHas('comments'))
                    ->get()
                    ->toArray();

        return $response;
    }
}
