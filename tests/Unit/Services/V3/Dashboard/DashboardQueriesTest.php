<?php

namespace Tests\Unit\Services\V3\Dashboard;

use App\Enums\SortDirection;
use App\Services\V3\Dashboard\DashboardQueries;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DashboardQueriesTest extends TestCase
{
    private const TABLE = 'project.dataset.views';

    private const TEAM_ID = 7;

    public static function timelineRanges(): array
    {
        return [
            'under 30 days buckets by day' => [
                '2024-01-01',
                '2024-01-30',
                "FORMAT_DATE('%Y-%m-%d', date)",
            ],
            '30 to 179 days buckets by week' => [
                '2024-01-01',
                '2024-01-31',
                "FORMAT_DATE('%Y-%W', DATE_TRUNC(date, WEEK))",
            ],
            '180 days or more buckets by month' => [
                '2024-01-01',
                '2024-06-29',
                "FORMAT_DATE('%Y-%m', DATE_TRUNC(date, MONTH))",
            ],
        ];
    }

    #[DataProvider('timelineRanges')]
    public function test_dataset_views_timeline_counts_team_dataset_views_per_bucket(
        string $start,
        string $end,
        string $bucketLabel,
    ): void {
        $query = DashboardQueries::datasetViewsTimeline(
            self::TABLE,
            self::TEAM_ID,
            CarbonImmutable::parse($start),
            CarbonImmutable::parse($end),
        );

        $this->assertSqlEquals(
            "SELECT {$bucketLabel} AS date, count(*) AS counter
             FROM project.dataset.views
             WHERE entity_type = 'dataset'
                AND team_id = @teamId
                AND date BETWEEN @startDate AND @endDate
             GROUP BY 1
             ORDER BY 1 ASC",
            $query->sql,
        );
        $this->assertSame(
            ['teamId' => self::TEAM_ID, 'startDate' => $start, 'endDate' => $end],
            $query->params,
        );
    }

    public static function rankDirections(): array
    {
        return [
            'most viewed first'  => [SortDirection::DESC, 'DESC'],
            'least viewed first' => [SortDirection::ASC, 'ASC'],
        ];
    }

    #[DataProvider('rankDirections')]
    public function test_dataset_views_ranked_counts_views_per_team_dataset_in_direction(
        SortDirection $direction,
        string $expectedOrder,
    ): void {
        $query = DashboardQueries::datasetViewsRanked(
            self::TABLE,
            self::TEAM_ID,
            CarbonImmutable::parse('2024-01-01'),
            CarbonImmutable::parse('2024-12-31'),
            $direction,
        );

        $this->assertSqlEquals(
            "SELECT entity_id, count(*) AS counter
             FROM project.dataset.views
             WHERE entity_type = 'dataset'
                AND team_id = @teamId
                AND date BETWEEN @startDate AND @endDate
             GROUP BY entity_id
             ORDER BY counter {$expectedOrder}",
            $query->sql,
        );
        $this->assertSame(
            ['teamId' => self::TEAM_ID, 'startDate' => '2024-01-01', 'endDate' => '2024-12-31'],
            $query->params,
        );
    }

    public function test_entity_views_counts_team_views_of_the_entity_type(): void
    {
        $query = DashboardQueries::entityViews(
            self::TABLE,
            'data-custodian',
            self::TEAM_ID,
            CarbonImmutable::parse('2024-01-01'),
            CarbonImmutable::parse('2024-12-31'),
        );

        $this->assertSqlEquals(
            "SELECT count(*) AS counter
             FROM project.dataset.views
             WHERE entity_type = @entityType
                AND team_id = @teamId
                AND date BETWEEN @startDate AND @endDate",
            $query->sql,
        );
        $this->assertSame(
            [
                'entityType' => 'data-custodian',
                'teamId'     => self::TEAM_ID,
                'startDate'  => '2024-01-01',
                'endDate'    => '2024-12-31',
            ],
            $query->params,
        );
    }

    private function assertSqlEquals(string $expected, string $actual): void
    {
        $normalise = fn (string $sql): string => trim(preg_replace('/\s+/', ' ', $sql));

        $this->assertSame($normalise($expected), $normalise($actual));
    }
}
