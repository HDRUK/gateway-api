<?php

namespace Tests\Unit\Services;

use App\Models\Collection;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Publication;
use App\Models\Team;
use App\Models\Tool;
use App\Services\BigQueryService;
use App\Services\V3\TeamDashboardService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class TeamDashboardServiceTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private TeamDashboardService $service;
    /** @var MockInterface&BigQueryService */
    private MockInterface $bigQueryMock;
    private int $teamId;

    public function setUp(): void
    {
        $this->commonSetUp();

        // Bind a mock BigQueryService so getDatasetViews never hits the real API
        $this->bigQueryMock = Mockery::mock(BigQueryService::class);
        $this->app->instance(BigQueryService::class, $this->bigQueryMock);

        $this->service = new TeamDashboardService($this->bigQueryMock);
        $this->teamId  = Team::query()->first()->id;
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // getCount – response structure
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_datause_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_tool_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_collection_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_publication_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    // -------------------------------------------------------------------------
    // getCount – return types are integers
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_datause_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_tool_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_collection_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_publication_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – values are non-negative
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_datause_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_tool_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_collection_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_publication_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – interval never exceeds total
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_datause_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_tool_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_collection_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_publication_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – with explicit date range
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_datause_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_tool_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_collection_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_publication_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    // -------------------------------------------------------------------------
    // getCount – far-future range yields zero interval
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_datause_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_tool_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_collection_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_publication_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – total is unaffected by date range
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');
        $withoutRange = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    public function test_get_datause_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Dur::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');
        $withoutRange = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    public function test_get_tool_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Tool::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');
        $withoutRange = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    public function test_get_collection_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Collection::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');
        $withoutRange = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    // -------------------------------------------------------------------------
    // getDatasetViews – delegates to BigQueryService
    // -------------------------------------------------------------------------

    public function test_get_dataset_views_returns_array(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([]);

        $result = $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-12-31');

        $this->assertIsArray($result);
    }

    public function test_get_dataset_views_passes_team_id_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql, array $params) {
                return $params['teamId'] === $this->teamId;
            })
            ->andReturn([]);

        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-12-31');
    }

    public function test_get_dataset_views_passes_date_range_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql, array $params) {
                return $params['startDate'] === '2024-01-01'
                    && $params['endDate'] === '2024-12-31';
            })
            ->andReturn([]);

        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-12-31');
    }

    public function test_get_dataset_views_returns_rows_from_bigquery(): void
    {
        $rows = [
            ['date' => '2024-01', 'counter' => 42],
            ['date' => '2024-02', 'counter' => 17],
        ];

        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn($rows);

        $result = $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-12-31');

        $this->assertCount(2, $result);
        $this->assertEquals($rows, $result);
    }

    // -------------------------------------------------------------------------
    // getDatasetViews – SQL granularity switches
    // -------------------------------------------------------------------------

    public function test_get_dataset_views_uses_monthly_granularity_for_range_over_180_days(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql) {
                return str_contains($sql, 'MONTH');
            })
            ->andReturn([]);

        // 365-day range → monthly
        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-12-31');
    }

    public function test_get_dataset_views_uses_weekly_granularity_for_range_between_30_and_180_days(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql) {
                return str_contains($sql, 'WEEK');
            })
            ->andReturn([]);

        // 90-day range → weekly
        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-03-31');
    }

    public function test_get_dataset_views_uses_daily_granularity_for_range_under_30_days(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql) {
                // Daily format has no TRUNC keyword — just the raw date column
                return !str_contains($sql, 'MONTH') && !str_contains($sql, 'WEEK');
            })
            ->andReturn([]);

        // 14-day range → daily
        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-01-14');
    }
}
