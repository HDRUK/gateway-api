<?php

namespace Tests\Unit\Controllers;

use App\Models\Team;
use App\Services\V3\TeamDashboardService;
use Mockery;
use Mockery\MockInterface;
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
    private MockInterface $serviceMock;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $this->serviceMock = Mockery::mock(TeamDashboardService::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

    // --- Invalid team ID ---

    public function test_get_dataset_count_with_invalid_team_id_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datasets/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_datause_count_with_invalid_team_id_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datauses/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_tool_count_with_invalid_team_id_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/tools/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_collection_count_with_invalid_team_id_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/collections/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    // --- Invalid date interval ---

    public function test_get_dataset_count_with_invalid_interval_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count?startDate=2026-01-01&endDate=2025-01-01';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_datause_count_with_invalid_interval_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datauses/count?startDate=2026-01-01&endDate=2025-01-01';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_tool_count_with_invalid_interval_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/tools/count?startDate=2026-01-01&endDate=2025-01-01';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_collection_count_with_invalid_interval_returns_without_success(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/count?startDate=2026-01-01&endDate=2025-01-01';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    // --- Unknown entity ---

    public function test_entity_count_returns_error_for_unknown_entity(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/unknown/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertStatus(500);
    }

    // --- Response structure ---

    public function test_get_dataset_count_returns_expected_json_structure(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_get_datause_count_returns_expected_json_structure(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datauses/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_get_tool_count_returns_expected_json_structure(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/tools/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_get_collection_count_returns_expected_json_structure(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/count';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    // --- Valid date interval passes through ---

    public function test_get_dataset_count_with_equal_start_and_end_date_returns_success(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count?startDate=2024-06-01&endDate=2024-06-01';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_dataset_count_with_valid_interval_returns_success(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count?startDate=2024-01-01&endDate=2024-12-31';

        $response = $this->json('GET', $url, [], $this->headerNonAdmin);

        $response->assertOk();
    }

    // --- Unauthenticated access ---

    public function test_get_dataset_count_without_auth_returns_unauthorised(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/count';

        $response = $this->json('GET', $url);

        $response->assertStatus(401);
    }

    public function test_get_datause_count_without_auth_returns_unauthorised(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datauses/count';

        $response = $this->json('GET', $url);

        $response->assertStatus(401);
    }

    public function test_get_tool_count_without_auth_returns_unauthorised(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/tools/count';

        $response = $this->json('GET', $url);

        $response->assertStatus(401);
    }

    public function test_get_collection_count_without_auth_returns_unauthorised(): void
    {
        $url = '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/count';

        $response = $this->json('GET', $url);

        $response->assertStatus(401);
    }
}
