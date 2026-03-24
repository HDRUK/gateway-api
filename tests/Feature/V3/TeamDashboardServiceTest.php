<?php

namespace Tests\Unit\Services;

use App\Models\Collection;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Team;
use App\Models\Tool;
use App\Services\V3\TeamDashboardService;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class TeamDashboardServiceTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private TeamDashboardService $service;
    private int $teamId;

    public function setUp(): void
    {
        $this->commonSetUp();
        $this->service = new TeamDashboardService();
        $this->teamId = Team::query()->first()->id;
    }

    // --- Response structure ---

    public function test_get_dataset_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_datause_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Dur::class, 'created_at', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_tool_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Tool::class, 'created_at', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_collection_count_returns_total_and_interval_keys(): void
    {
        $result = $this->service->getCount(Collection::class, 'created_at', $this->teamId, null, null);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    // --- Return types are integers ---

    public function test_get_dataset_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_datause_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Dur::class, 'created_at', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_tool_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Tool::class, 'created_at', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    public function test_get_collection_count_returns_integer_values(): void
    {
        $result = $this->service->getCount(Collection::class, 'created_at', $this->teamId, null, null);

        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['total_by_interval']);
    }

    // --- Values are non-negative ---

    public function test_get_dataset_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_datause_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Dur::class, 'created_at', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_tool_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Tool::class, 'created_at', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    public function test_get_collection_count_values_are_non_negative(): void
    {
        $result = $this->service->getCount(Collection::class, 'created_at', $this->teamId, null, null);

        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total_by_interval']);
    }

    // --- interval never exceeds total ---

    public function test_get_dataset_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_datause_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Dur::class, 'created_at', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_tool_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Tool::class, 'created_at', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    public function test_get_collection_count_interval_does_not_exceed_total(): void
    {
        $result = $this->service->getCount(Collection::class, 'created_at', $this->teamId, null, null);

        $this->assertLessThanOrEqual($result['total'], $result['total_by_interval']);
    }

    // --- With date range ---

    public function test_get_dataset_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_datause_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Dur::class, 'created_at', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_tool_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Tool::class, 'created_at', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    public function test_get_collection_count_with_date_range_returns_correct_keys(): void
    {
        $result = $this->service->getCount(Collection::class, 'created_at', $this->teamId, '2024-01-01', '2024-12-31');

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('total_by_interval', $result);
    }

    // --- Far-future range yields zero interval ---

    public function test_get_dataset_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Dataset::class, 'active_date', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_datause_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Dur::class, 'created_at', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_tool_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Tool::class, 'created_at', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }

    public function test_get_collection_count_with_future_date_range_returns_zero_interval(): void
    {
        $result = $this->service->getCount(Collection::class, 'created_at', $this->teamId, '2099-01-01', '2099-12-31');

        $this->assertEquals(0, $result['total_by_interval']);
    }
}
