<?php

namespace App\Services\V3;

class TeamDashboardService
{
    public function getCount(string $model, string $dateColumn, int $teamId, $startDate, $endDate)
    {
        $base = $model::where([
            'team_id' => $teamId,
            'status'  => $model::STATUS_ACTIVE,
        ]);

        $total = (clone $base)->count();

        $intervalQuery = clone $base;

        if ($startDate && $endDate) {
            $intervalQuery->whereBetween($dateColumn, [$startDate, $endDate]);
        } else {
            $intervalQuery->where($dateColumn, '>=', now()->subMonths(12));
        }

        return [
            'total'             => $total,
            'total_by_interval' => $intervalQuery->count(),
        ];
    }
}
