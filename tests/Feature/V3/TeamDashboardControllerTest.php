<?php

namespace Tests\Unit\Controllers;

use App\Models\Team;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class TeamDashboardControllerTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $headerNonAdmin;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->headerNonAdmin = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];
    }

    private function getValidTeamId(): int
    {
        return Team::query()->first()->id;
    }

    private function getInvalidTeamId(): int
    {
        $latest = Team::query()->orderBy('id', 'desc')->first();
        return $latest ? $latest->id + 1 : 1;
    }

    // --- Unauthenticated access ---

    public function test_get_dataset_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count');

        $response->assertStatus(401);
    }

    public function test_get_datause_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datauses/count');

        $response->assertStatus(401);
    }

    public function test_get_tool_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/tools/count');

        $response->assertStatus(401);
    }

    public function test_get_collection_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/count');

        $response->assertStatus(401);
    }

    public function test_get_publication_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/publications/count');

        $response->assertStatus(401);
    }

    public function test_get_dataset_views_360_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views');

        $response->assertStatus(401);
    }

    // --- Invalid team ID ---

    public function test_get_dataset_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datasets/count', [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_datause_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datauses/count', [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_tool_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/tools/count', [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_collection_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/collections/count', [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_publication_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/publications/count', [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    // --- Invalid date interval ---

    public function test_get_dataset_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_datause_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datauses/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_tool_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/tools/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_collection_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_publication_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/publications/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_dataset_views_360_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    // --- Unknown entity returns ok with empty data ---

    public function test_entity_count_returns_ok_for_unknown_entity(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/unknown/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertEmpty($response->decodeResponseJson()['data']);
    }

    // --- Response structure ---

    public function test_get_dataset_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_datause_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datauses/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_tool_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/tools/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_collection_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_publication_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/publications/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_dataset_views_360_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    // --- dataset_views_360: data is an array ---

    public function test_get_dataset_views_360_returns_array_data(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_dataset_views_360_each_row_has_date_and_counter_keys(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();

        foreach ($response->decodeResponseJson()['data'] as $row) {
            $this->assertArrayHasKey('date', $row);
            $this->assertArrayHasKey('counter', $row);
        }
    }

    public function test_get_dataset_views_360_counter_values_are_numeric(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();

        foreach ($response->decodeResponseJson()['data'] as $row) {
            $this->assertIsNumeric($row['counter']);
        }
    }

    // --- Valid date interval passes through ---

    public function test_get_dataset_count_with_equal_start_and_end_date_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count?startDate=2024-06-01&endDate=2024-06-01', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_dataset_count_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_dataset_views_360_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    // --- dataset_views_360: no date params defaults to last 12 months ---

    public function test_get_dataset_views_360_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/360/datasets/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }
}
