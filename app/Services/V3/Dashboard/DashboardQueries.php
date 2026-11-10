<?php

namespace App\Services\V3\Dashboard;

use App\Enums\SortDirection;
use App\Enums\ViewBucket;
use Carbon\CarbonImmutable;

class DashboardQueries
{
    public static function datasetViewsTimeline(
        string $table,
        int $teamId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): BigQueryQuery {
        $bucketLabel = ViewBucket::forRange($start, $end)->sqlLabel();

        return new BigQueryQuery(
            sql: "
                SELECT {$bucketLabel} AS date, count(*) AS counter
                FROM {$table}
                WHERE entity_type = 'dataset'
                   AND team_id = @teamId
                   AND date BETWEEN @startDate AND @endDate
                GROUP BY 1
                ORDER BY 1 ASC
            ",
            params: self::rangeParams($teamId, $start, $end),
        );
    }

    public static function datasetViewsRanked(
        string $table,
        int $teamId,
        CarbonImmutable $start,
        CarbonImmutable $end,
        SortDirection $direction,
    ): BigQueryQuery {
        return new BigQueryQuery(
            sql: "
                SELECT entity_id, count(*) AS counter
                FROM {$table}
                WHERE entity_type = 'dataset'
                   AND team_id = @teamId
                   AND date BETWEEN @startDate AND @endDate
                GROUP BY entity_id
                ORDER BY counter {$direction->value}
            ",
            params: self::rangeParams($teamId, $start, $end),
        );
    }

    public static function entityViews(
        string $table,
        string $entityType,
        int $teamId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): BigQueryQuery {
        return new BigQueryQuery(
            sql: "
                SELECT count(*) AS counter
                FROM {$table}
                WHERE entity_type = @entityType
                   AND team_id = @teamId
                   AND date BETWEEN @startDate AND @endDate
            ",
            params: ['entityType' => $entityType, ...self::rangeParams($teamId, $start, $end)],
        );
    }

    /**
     * @return array{teamId: int, startDate: string, endDate: string}
     */
    private static function rangeParams(int $teamId, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return [
            'teamId'    => $teamId,
            'startDate' => $start->toDateString(),
            'endDate'   => $end->toDateString(),
        ];
    }
}
