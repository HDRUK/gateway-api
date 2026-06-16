<?php

namespace Tests\Unit\Services;

use App\Models\Collection;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\EnquiryThread;
use App\Models\Publication;
use App\Models\Team;
use App\Models\TeamHasDataAccessApplication;
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
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null, ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_datause_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null, ['status' => Dur::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_tool_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null, ['status' => Tool::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_collection_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null, ['status' => Collection::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_publication_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null, ['status' => Publication::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_general_enquiry_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_general_enquiry' => 1]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_feasibility_enquiry_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_feasibility_enquiry' => 1]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_data_access_request_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(TeamHasDataAccessApplication::class, 'created_at', $this->teamId, null, null, []);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    // -------------------------------------------------------------------------
    // getCount – return types are integers
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null, ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_datause_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null, ['status' => Dur::STATUS_ACTIVE]);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_tool_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null, ['status' => Tool::STATUS_ACTIVE]);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_collection_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null, ['status' => Collection::STATUS_ACTIVE]);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_publication_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null, ['status' => Publication::STATUS_ACTIVE]);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_general_enquiry_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_general_enquiry' => 1]);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_feasibility_enquiry_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_feasibility_enquiry' => 1]);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_data_access_request_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(TeamHasDataAccessApplication::class, 'created_at', $this->teamId, null, null, []);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – values are non-negative
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null, ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_datause_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null, ['status' => Dur::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_tool_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null, ['status' => Tool::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_collection_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null, ['status' => Collection::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_publication_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null, ['status' => Publication::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_general_enquiry_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_general_enquiry' => 1]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_feasibility_enquiry_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_feasibility_enquiry' => 1]);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_data_access_request_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(TeamHasDataAccessApplication::class, 'created_at', $this->teamId, null, null, []);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – interval never exceeds total
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null, ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_datause_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null, ['status' => Dur::STATUS_ACTIVE]);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_tool_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null, ['status' => Tool::STATUS_ACTIVE]);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_collection_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null, ['status' => Collection::STATUS_ACTIVE]);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_publication_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, null, null, ['status' => Publication::STATUS_ACTIVE]);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_general_enquiry_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_general_enquiry' => 1]);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_feasibility_enquiry_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, null, null, ['is_feasibility_enquiry' => 1]);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_data_access_request_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(TeamHasDataAccessApplication::class, 'created_at', $this->teamId, null, null, []);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – with explicit date range
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_datause_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Dur::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_tool_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Tool::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_collection_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Collection::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_publication_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Publication::STATUS_ACTIVE]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_general_enquiry_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, '2024-01-01', '2024-12-31', ['is_general_enquiry' => 1]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_feasibility_enquiry_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, '2024-01-01', '2024-12-31', ['is_feasibility_enquiry' => 1]);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_data_access_request_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(TeamHasDataAccessApplication::class, 'created_at', $this->teamId, '2024-01-01', '2024-12-31', []);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    // -------------------------------------------------------------------------
    // getCount – far-future range yields zero interval
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31', ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_datause_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Dur::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31', ['status' => Dur::STATUS_ACTIVE]);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_tool_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Tool::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31', ['status' => Tool::STATUS_ACTIVE]);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_collection_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Collection::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31', ['status' => Collection::STATUS_ACTIVE]);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_publication_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Publication::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31', ['status' => Publication::STATUS_ACTIVE]);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_general_enquiry_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, '2099-01-01', '2099-12-31', ['is_general_enquiry' => 1]);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_feasibility_enquiry_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(EnquiryThread::class, 'created_at', $this->teamId, '2099-01-01', '2099-12-31', ['is_feasibility_enquiry' => 1]);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_data_access_request_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(TeamHasDataAccessApplication::class, 'created_at', $this->teamId, '2099-01-01', '2099-12-31', []);

        $this->assertEquals(0, $result['total_by_interval']);
    }

    // -------------------------------------------------------------------------
    // getCount – total is unaffected by date range
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Dataset::STATUS_ACTIVE]);
        $withoutRange = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null, ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    public function test_get_datause_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Dur::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Dur::STATUS_ACTIVE]);
        $withoutRange = $this->service->getCount(Dur::class, 'active_date', $this->teamId, null, null, ['status' => Dur::STATUS_ACTIVE]);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    public function test_get_tool_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Tool::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Tool::STATUS_ACTIVE]);
        $withoutRange = $this->service->getCount(Tool::class, 'active_date', $this->teamId, null, null, ['status' => Tool::STATUS_ACTIVE]);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    public function test_get_collection_count_total_is_same_regardless_of_date_range(): void
    {
        $withRange    = $this->service->getCount(Collection::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31', ['status' => Collection::STATUS_ACTIVE]);
        $withoutRange = $this->service->getCount(Collection::class, 'active_date', $this->teamId, null, null, ['status' => Collection::STATUS_ACTIVE]);

        $this->assertEquals($withoutRange['total'], $withRange['total']);
    }

    // -------------------------------------------------------------------------
    // getCount – end date includes full day (23:59:59)
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_interval_includes_end_of_day(): void
    {
        // Seed a dataset with active_date matching end of day boundary
        $dataset = Dataset::factory()->create([
            'team_id'     => $this->teamId,
            'status'      => Dataset::STATUS_ACTIVE,
            'active_date' => '2024-06-01 23:30:00',
        ]);

        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2024-06-01', '2024-06-01', ['status' => Dataset::STATUS_ACTIVE]);

        $this->assertGreaterThanOrEqual(1, $result['total_by_interval']);

        $dataset->forceDelete();
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

        $this->addToAssertionCount(1);
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

        $this->addToAssertionCount(1);
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

        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-12-31');

        $this->addToAssertionCount(1);
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

        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-03-31');

        $this->addToAssertionCount(1);
    }

    public function test_get_dataset_views_uses_daily_granularity_for_range_under_30_days(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql) {
                return !str_contains($sql, 'MONTH') && !str_contains($sql, 'WEEK');
            })
            ->andReturn([]);

        $this->service->getDatasetViews($this->teamId, '2024-01-01', '2024-01-14');

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // getDatatasetViewsTop – delegates to BigQueryService
    // -------------------------------------------------------------------------

    public function test_get_dataset_views_top_returns_array(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([]);

        $result = $this->service->getDatatasetViewsTop($this->teamId, '2024-01-01', '2024-12-31');

        $this->assertIsArray($result);
    }

    public function test_get_dataset_views_top_passes_team_id_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql, array $params) {
                return $params['teamId'] === $this->teamId;
            })
            ->andReturn([]);

        $this->service->getDatatasetViewsTop($this->teamId, '2024-01-01', '2024-12-31');

        $this->addToAssertionCount(1);
    }

    public function test_get_dataset_views_top_passes_date_range_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql, array $params) {
                return $params['startDate'] === '2024-01-01'
                    && $params['endDate'] === '2024-12-31';
            })
            ->andReturn([]);

        $this->service->getDatatasetViewsTop($this->teamId, '2024-01-01', '2024-12-31');

        $this->addToAssertionCount(1);
    }

    public function test_get_dataset_views_top_returns_empty_array_when_bigquery_returns_no_rows(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([]);

        $result = $this->service->getDatatasetViewsTop($this->teamId, '2024-01-01', '2024-12-31');

        $this->assertCount(0, $result);
    }

    public function test_get_dataset_views_top_each_row_has_expected_keys(): void
    {
        $dataset = Dataset::whereHas('versions', function ($q) {
            $q->whereNotNull('short_title');
        })->where('team_id', $this->teamId)->first();

        if (!$dataset) {
            $this->markTestSkipped('No dataset with a versioned short_title available for team.');
        }

        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([
                ['entity_id' => $dataset->id, 'counter' => 10],
            ]);

        $result = $this->service->getDatatasetViewsTop($this->teamId, '2024-01-01', '2024-12-31');

        $this->assertNotEmpty($result, 'Expected at least one row — dataset may be missing a short_title.');

        foreach ($result as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('title', $row);
            $this->assertArrayHasKey('counter', $row);
        }
    }

    public function test_get_dataset_views_top_excludes_rows_with_no_matching_dataset(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([
                ['entity_id' => 999999, 'counter' => 5],
            ]);

        $result = $this->service->getDatatasetViewsTop($this->teamId, '2024-01-01', '2024-12-31');

        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // getEntityViews – delegates to BigQueryService
    // -------------------------------------------------------------------------

    public function test_get_entity_views_returns_array(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([['counter' => 5]]);

        $result = $this->service->getEntityViews('collection', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertIsArray($result);
    }

    public function test_get_entity_views_passes_team_id_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql, array $params) {
                return $params['teamId'] === $this->teamId;
            })
            ->andReturn([['counter' => 0]]);

        $this->service->getEntityViews('collection', $this->teamId, '2024-01-01', '2024-12-31');

        $this->addToAssertionCount(1);
    }

    public function test_get_entity_views_passes_date_range_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql, array $params) {
                return $params['startDate'] === '2024-01-01'
                    && $params['endDate'] === '2024-12-31';
            })
            ->andReturn([['counter' => 0]]);

        $this->service->getEntityViews('collection', $this->teamId, '2024-01-01', '2024-12-31');

        $this->addToAssertionCount(1);
    }

    public function test_get_entity_views_passes_collection_entity_type_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql) {
                return str_contains($sql, "'collection'");
            })
            ->andReturn([['counter' => 0]]);

        $this->service->getEntityViews('collection', $this->teamId, '2024-01-01', '2024-12-31');

        $this->addToAssertionCount(1);
    }

    public function test_get_entity_views_passes_data_custodian_entity_type_to_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->withArgs(function (string $sql) {
                return str_contains($sql, "'data-custodian'");
            })
            ->andReturn([['counter' => 0]]);

        $this->service->getEntityViews('data-custodian', $this->teamId, '2024-01-01', '2024-12-31');

        $this->addToAssertionCount(1);
    }

    public function test_get_entity_views_returns_counter_from_bigquery(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([['counter' => 42]]);

        $result = $this->service->getEntityViews('collection', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertEquals(42, $result[0]['counter']);
    }

    public function test_get_entity_views_returns_zero_counter_when_no_results(): void
    {
        $this->bigQueryMock
            ->shouldReceive('query')
            ->once()
            ->andReturn([['counter' => 0]]);

        $result = $this->service->getEntityViews('collection', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertEquals(0, $result[0]['counter']);
    }
}
