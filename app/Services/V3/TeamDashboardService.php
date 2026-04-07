<?php

namespace App\Services\V3;

use App\Models\Dataset;
use App\Services\BigQueryService;
use Carbon\Carbon;

class TeamDashboardService
{
    public function __construct(
        private readonly BigQueryService $bigQueryService,
    ) {
    }

    public function getCount(string $model, string $dateColumn, int $teamId, $startDate, $endDate, array $extraColumn)
    {
        $base = $model::where('team_id', $teamId);

        if (count($extraColumn)) {
            $base->where($extraColumn);
        }

        $total = (clone $base)->count();

        $intervalQuery = clone $base;

        if ($startDate && $endDate) {
            $intervalQuery->whereBetween($dateColumn, [$startDate, $endDate . ' 23:59:59']);
        } else {
            $intervalQuery->where($dateColumn, '>=', now()->subMonths(12));
        }

        return [
            'total'             => $total,
            'total_by_interval' => $intervalQuery->count(),
        ];
    }

    public function getDatasetViews(int $teamId, $startDate, $endDate)
    {
        $from = config('services.googlebigquery.project_id') . '.' . config('services.googlebigquery.dashboard_dataset') . '.' . config('services.googlebigquery.dashboard_table');

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);
        $diffInDays = $start->diffInDays($end);

        if ($diffInDays >= 180) {
            // Monthly
            $dateTrunc  = "FORMAT_DATE('%b', DATE_TRUNC(date, MONTH))";
        } elseif ($diffInDays >= 30) {
            // Weekly
            $dateTrunc  = "FORMAT_DATE('%W', DATE_TRUNC(date, WEEK))";
        } else {
            // Daily
            $dateTrunc  = "FORMAT_DATE('%d', date)";
        }

        $sql = "
            SELECT {$dateTrunc} AS date, count(*) AS counter
            FROM {$from}
            WHERE entity_type = 'dataset'
               AND team_id = @teamId
               AND date BETWEEN @startDate AND @endDate
            GROUP BY 1
            ORDER BY 1 ASC
        ";

        $params = [
            'teamId'    => $teamId,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ];

        return $this->bigQueryService->query($sql, $params);
    }

    public function getDatatasetViewsTop(int $teamId, $startDate, $endDate)
    {
        $from = config('services.googlebigquery.project_id') . '.' . config('services.googlebigquery.dashboard_dataset') . '.' . config('services.googlebigquery.dashboard_table');

        $sql = "
            SELECT entity_id, count(*) AS counter
            FROM {$from}
            WHERE entity_type = 'dataset'
               AND team_id = @teamId
               AND date BETWEEN @startDate AND @endDate
            GROUP BY entity_id
            ORDER BY counter DESC
        ";

        $params = [
            'teamId' => $teamId,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ];

        $responseBq = $this->bigQueryService->query($sql, $params);

        $response = [];

        foreach ($responseBq as $row) {
            $dataset = Dataset::where('id', $row['entity_id'])->first();

            if (!$dataset) {
                continue;
            }

            $shortTitle = $dataset->latestVersion(['short_title'])->short_title ?? null;

            if ($shortTitle) {
                $response[] = [
                    'id' => $row['entity_id'],
                    'title' => $shortTitle,
                    'counter' => $row['counter'],
                ];
            }
        }

        return $response;
    }

    public function getEntityViews(string $entity, int $teamId, $startDate, $endDate)
    {
        $from = config('services.googlebigquery.project_id') . '.' . config('services.googlebigquery.dashboard_dataset') . '.' . config('services.googlebigquery.dashboard_table');

        $sql = "
            SELECT count(*) AS counter
            FROM {$from}
            WHERE entity_type = '{$entity}'
               AND team_id = @teamId
               AND date BETWEEN @startDate AND @endDate
        ";

        $params = [
            'teamId' => $teamId,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ];

        return $this->bigQueryService->query($sql, $params);
    }
}
