<?php

namespace Tests\Unit\Controllers;

use App\Models\Role;
use App\Models\Team;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use App\Models\UserHasRole;
use App\Services\V3\TeamDashboardService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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

    protected $headerAdmin;

    /** @var MockInterface&TeamDashboardService */
    private MockInterface $serviceMock;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->headerNonAdmin = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];
        $this->assignTeamRole($this->getUserFromJwt($nonAdminJwt)['id'], $this->getValidTeamId(), 'custodian.team.admin');

        $this->authorisationUser(true);
        $this->headerAdmin = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->getAuthorisationJwt(true),
        ];

        $this->serviceMock = Mockery::mock(TeamDashboardService::class);

        $this->serviceMock
            ->shouldReceive('getCount')
            ->andReturn(['total' => 0, 'total_by_interval' => 0]);

        $this->serviceMock
            ->shouldReceive('getDatasetViews')
            ->andReturn([]);

        $this->serviceMock
            ->shouldReceive('getDatatasetViewsTop')
            ->andReturn([]);

        $this->serviceMock
            ->shouldReceive('getEntityViews')
            ->andReturn([['counter' => 0]]);

        $this->serviceMock
            ->shouldReceive('getDatasetViewsBottom')
            ->andReturn([])
            ->byDefault();

        $this->app->instance(TeamDashboardService::class, $this->serviceMock);
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

    // -------------------------------------------------------------------------
    // Unauthenticated access
    // -------------------------------------------------------------------------

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

    public function test_get_general_enquiry_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/general-enquires/count');

        $response->assertStatus(401);
    }

    public function test_get_feasibility_enquiry_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/fesability-enquires/count');

        $response->assertStatus(401);
    }

    public function test_get_data_access_request_count_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/data-access-requests/count');

        $response->assertStatus(401);
    }

    public function test_get_dataset_views_timeline_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline');

        $response->assertStatus(401);
    }

    public function test_get_dataset_views_top_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top');

        $response->assertStatus(401);
    }

    public function test_get_dataset_views_bottom_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/bottom');

        $response->assertStatus(401);
    }

    public function test_get_collection_views_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views');

        $response->assertStatus(401);
    }

    public function test_get_datacustodian_views_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views');

        $response->assertStatus(401);
    }

    public function test_download_csv_without_auth_returns_unauthorised(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/download/csv');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Invalid team ID
    // -------------------------------------------------------------------------

    public function test_get_dataset_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datasets/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_datause_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datauses/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_tool_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/tools/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_collection_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/collections/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_publication_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/publications/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_general_enquiry_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/general-enquires/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_feasibility_enquiry_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/fesability-enquires/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_data_access_request_count_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/data-access-requests/count', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_dataset_views_top_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datasets/views/top', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_dataset_views_bottom_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datasets/views/bottom', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_collection_views_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/collections/views', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_get_datacustodian_views_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/datacustodians/views', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    public function test_download_csv_with_invalid_team_id_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getInvalidTeamId() . '/dashboard/download/csv', [], $this->headerAdmin);

        $response->assertStatus(400);
        $this->assertEquals('Invalid argument(s)', $response->decodeResponseJson()['message']);
    }

    // -------------------------------------------------------------------------
    // Invalid date interval
    // -------------------------------------------------------------------------

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

    public function test_get_general_enquiry_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/general-enquires/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_feasibility_enquiry_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/fesability-enquires/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_data_access_request_count_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/data-access-requests/count?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_dataset_views_timeline_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_dataset_views_top_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_collection_views_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_get_datacustodian_views_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    public function test_download_csv_with_invalid_interval_returns_without_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/download/csv?startDate=2026-01-01&endDate=2025-01-01', [], $this->headerNonAdmin);

        $response->assertStatus(500);
        $this->assertEquals('startDate must be less than or equal to endDate', $response->decodeResponseJson()['data']);
    }

    // -------------------------------------------------------------------------
    // Unknown entity returns error
    // -------------------------------------------------------------------------

    public function test_entity_count_with_unknown_entity_returns_error(): void
    {
        // The {entity} route constraint (config/routes_v3.php) rejects
        // anything outside the known list before the controller runs, so
        // this is a genuine unmatched route — a 404, not a 500.
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/unknown/count', [], $this->headerNonAdmin);
        $response->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Response structure
    // -------------------------------------------------------------------------

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

    public function test_get_general_enquiry_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/general-enquires/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_feasibility_enquiry_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/fesability-enquires/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_data_access_request_count_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/data-access-requests/count', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data' => ['total', 'total_by_interval']]);
    }

    public function test_get_dataset_views_timeline_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_get_dataset_views_top_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_get_collection_views_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_get_datacustodian_views_returns_expected_json_structure(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'data']);
    }

    // -------------------------------------------------------------------------
    // dataset_views_timeline: data is an array
    // -------------------------------------------------------------------------

    public function test_get_dataset_views_timeline_returns_array_data(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_dataset_views_timeline_each_row_has_date_and_counter_keys(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();

        foreach ($response->decodeResponseJson()['data'] as $row) {
            $this->assertArrayHasKey('date', $row);
            $this->assertArrayHasKey('counter', $row);
        }
    }

    public function test_get_dataset_views_timeline_counter_values_are_numeric(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();

        foreach ($response->decodeResponseJson()['data'] as $row) {
            $this->assertIsNumeric($row['counter']);
        }
    }

    // -------------------------------------------------------------------------
    // dataset_views_top: data is an array
    // -------------------------------------------------------------------------

    public function test_get_dataset_views_top_returns_array_data(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_dataset_views_top_each_row_has_id_title_and_counter_keys(): void
    {
        $this->serviceMock
            ->shouldReceive('getDatatasetViewsTop')
            ->andReturn([
                ['id' => 1, 'title' => 'Dataset A', 'counter' => 42],
            ]);

        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();

        foreach ($response->decodeResponseJson()['data'] as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('title', $row);
            $this->assertArrayHasKey('counter', $row);
        }
    }

    public function test_get_dataset_views_top_counter_values_are_numeric(): void
    {
        $this->serviceMock
            ->shouldReceive('getDatatasetViewsTop')
            ->andReturn([
                ['id' => 1, 'title' => 'Dataset A', 'counter' => 42],
                ['id' => 2, 'title' => 'Dataset B', 'counter' => 7],
            ]);

        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();

        foreach ($response->decodeResponseJson()['data'] as $row) {
            $this->assertIsNumeric($row['counter']);
        }
    }

    // -------------------------------------------------------------------------
    // dataset_views_bottom
    // -------------------------------------------------------------------------

    public function test_get_dataset_views_bottom_returns_least_viewed_datasets(): void
    {
        $leastViewed = [
            ['id' => 2, 'title' => 'Dataset B', 'counter' => 1],
            ['id' => 1, 'title' => 'Dataset A', 'counter' => 7],
        ];

        $this->serviceMock
            ->shouldReceive('getDatasetViewsBottom')
            ->andReturn($leastViewed);

        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/bottom?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertSame($leastViewed, $response->decodeResponseJson()['data']);
    }

    // -------------------------------------------------------------------------
    // collection_views and datacustodian_views: data is an array
    // -------------------------------------------------------------------------

    public function test_get_collection_views_returns_array_data(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_datacustodian_views_returns_array_data(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_collection_views_counter_value_is_numeric(): void
    {
        $this->serviceMock
            ->shouldReceive('getEntityViews')
            ->andReturn([['counter' => 15]]);

        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsNumeric($response->decodeResponseJson()['data'][0]['counter']);
    }

    public function test_get_datacustodian_views_counter_value_is_numeric(): void
    {
        $this->serviceMock
            ->shouldReceive('getEntityViews')
            ->andReturn([['counter' => 8]]);

        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsNumeric($response->decodeResponseJson()['data'][0]['counter']);
    }

    // -------------------------------------------------------------------------
    // Valid date interval passes through
    // -------------------------------------------------------------------------

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

    public function test_get_general_enquiry_count_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/general-enquires/count?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_feasibility_enquiry_count_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/fesability-enquires/count?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_data_access_request_count_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/data-access-requests/count?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_dataset_views_timeline_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_dataset_views_top_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_dataset_views_top_with_equal_start_and_end_date_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top?startDate=2024-06-01&endDate=2024-06-01', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_collection_views_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_collection_views_with_equal_start_and_end_date_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views?startDate=2024-06-01&endDate=2024-06-01', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_datacustodian_views_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_datacustodian_views_with_equal_start_and_end_date_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views?startDate=2024-06-01&endDate=2024-06-01', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // No date params defaults to last 12 months
    // -------------------------------------------------------------------------

    public function test_get_dataset_views_timeline_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/timeline', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_dataset_views_top_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datasets/views/top', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_collection_views_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/collections/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_datacustodian_views_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/datacustodians/views', [], $this->headerNonAdmin);

        $response->assertOk();
        $this->assertIsArray($response->decodeResponseJson()['data']);
    }

    public function test_get_general_enquiry_count_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/general-enquires/count', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_feasibility_enquiry_count_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/fesability-enquires/count', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_get_data_access_request_count_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/data-access-requests/count', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // downloadCsv – valid requests
    // -------------------------------------------------------------------------

    public function test_download_csv_returns_csv_file(): void
    {
        $response = $this->get('/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/download/csv', $this->headerNonAdmin);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_download_csv_with_valid_interval_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/download/csv?startDate=2024-01-01&endDate=2024-12-31', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_download_csv_with_equal_start_and_end_date_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/download/csv?startDate=2024-06-01&endDate=2024-06-01', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_download_csv_without_dates_returns_success(): void
    {
        $response = $this->json('GET', '/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/download/csv', [], $this->headerNonAdmin);

        $response->assertOk();
    }

    public function test_download_csv_content_disposition_header_contains_filename(): void
    {
        $response = $this->get('/api/v3/teams/' . $this->getValidTeamId() . '/dashboard/download/csv', $this->headerNonAdmin);

        $response->assertOk();
        $this->assertStringContainsString('dashboard.csv', $response->headers->get('Content-Disposition'));
    }

    // -------------------------------------------------------------------------
    // Team-scoped access: dashboard.read
    // -------------------------------------------------------------------------

    public static function dashboardRoutes(): array
    {
        return [
            'entity count'           => ['datasets/count'],
            'dataset views timeline' => ['datasets/views/timeline'],
            'most viewed datasets'   => ['datasets/views/top'],
            'least viewed datasets'  => ['datasets/views/bottom'],
            'collection views'       => ['collections/views'],
            'data custodian views'   => ['datacustodians/views'],
            'csv download'           => ['download/csv'],
        ];
    }

    public static function rolesWithDashboardAccess(): array
    {
        return [
            'team admin'                 => ['custodian.team.admin'],
            'metadata manager'           => ['custodian.metadata.manager'],
            'dar manager'                => ['custodian.dar.manager'],
            'team cohort discovery admin' => ['custodian.team.cohortAdmin'],
        ];
    }

    public static function rolesWithoutDashboardAccess(): array
    {
        return [
            'metadata editor' => ['metadata.editor'],
            'dar reviewer'    => ['dar.reviewer'],
            'developer'       => ['developer'],
        ];
    }

    public static function rolesWithoutDashboardAccessOnEachRoute(): array
    {
        $cases = [];

        foreach (self::rolesWithoutDashboardAccess() as $roleLabel => [$role]) {
            foreach (self::dashboardRoutes() as $routeLabel => [$route]) {
                $cases["{$roleLabel} on {$routeLabel}"] = [$role, $route];
            }
        }

        return $cases;
    }

    #[DataProvider('dashboardRoutes')]
    public function test_user_outside_the_team_cannot_read_its_dashboard(string $route): void
    {
        $header = $this->headerForUserWithoutRoles();

        $response = $this->json('GET', $this->dashboardUrl($this->getValidTeamId(), $route), [], $header);

        $response->assertStatus(401);
    }

    #[DataProvider('dashboardRoutes')]
    public function test_dashboard_reader_in_another_team_cannot_read_this_teams_dashboard(string $route): void
    {
        [$userId, $header] = $this->secondNonAdminUserWithoutRoles();
        $this->assignTeamRole($userId, $this->getOtherTeamId(), 'custodian.team.admin');

        $response = $this->json('GET', $this->dashboardUrl($this->getValidTeamId(), $route), [], $header);

        $response->assertStatus(401);
    }

    #[DataProvider('dashboardRoutes')]
    public function test_team_admin_can_read_their_teams_dashboard(string $route): void
    {
        [$userId, $header] = $this->secondNonAdminUserWithoutRoles();
        $this->assignTeamRole($userId, $this->getValidTeamId(), 'custodian.team.admin');

        $response = $this->json('GET', $this->dashboardUrl($this->getValidTeamId(), $route), [], $header);

        $response->assertOk();
    }

    #[DataProvider('dashboardRoutes')]
    public function test_hdruk_admin_can_read_any_teams_dashboard(string $route): void
    {
        [$userId, $header] = $this->secondNonAdminUserWithoutRoles();
        UserHasRole::create([
            'user_id' => $userId,
            'role_id' => Role::where('name', 'hdruk.admin')->firstOrFail()->id,
        ]);

        $response = $this->json('GET', $this->dashboardUrl($this->getValidTeamId(), $route), [], $header);

        $response->assertOk();
    }

    #[DataProvider('dashboardRoutes')]
    public function test_gateway_admin_can_read_any_teams_dashboard(string $route): void
    {
        $response = $this->json('GET', $this->dashboardUrl($this->getValidTeamId(), $route), [], $this->headerAdmin);

        $response->assertOk();
    }

    #[DataProvider('rolesWithDashboardAccess')]
    public function test_team_roles_with_dashboard_access_can_read_their_teams_dashboard(string $role): void
    {
        [$userId, $header] = $this->secondNonAdminUserWithoutRoles();
        $this->assignTeamRole($userId, $this->getValidTeamId(), $role);

        $response = $this->json('GET', $this->dashboardUrl($this->getValidTeamId(), 'datasets/count'), [], $header);

        $response->assertOk();
    }

    #[DataProvider('rolesWithoutDashboardAccessOnEachRoute')]
    public function test_team_roles_without_dashboard_access_cannot_read_their_teams_dashboard(
        string $role,
        string $route,
    ): void {
        [$userId, $header] = $this->secondNonAdminUserWithoutRoles();
        $this->assignTeamRole($userId, $this->getValidTeamId(), $role);

        $response = $this->json('GET', $this->dashboardUrl($this->getValidTeamId(), $route), [], $header);

        $response->assertStatus(401);
    }

    private function dashboardUrl(int $teamId, string $route): string
    {
        return '/api/v3/teams/' . $teamId . '/dashboard/' . $route;
    }

    private function getOtherTeamId(): int
    {
        return Team::query()->where('id', '!=', $this->getValidTeamId())->firstOrFail()->id;
    }

    private function headerForUserWithoutRoles(): array
    {
        return $this->secondNonAdminUserWithoutRoles()[1];
    }

    /**
     * The seeded test users are given random team roles, so every role this
     * user holds is removed before a scenario assigns the one it needs.
     *
     * @return array{0: int, 1: array<string, string>}
     */
    private function secondNonAdminUserWithoutRoles(): array
    {
        $this->authorisationUser(false, 2);
        $jwt = $this->getAuthorisationJwt(false, 2);
        $userId = $this->getUserFromJwt($jwt)['id'];

        $teamHasUserIds = TeamHasUser::where('user_id', $userId)->pluck('id');
        TeamUserHasRole::whereIn('team_has_user_id', $teamHasUserIds)->delete();
        TeamHasUser::whereIn('id', $teamHasUserIds)->delete();
        UserHasRole::where('user_id', $userId)->delete();

        return [$userId, [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ]];
    }

    private function assignTeamRole(int $userId, int $teamId, string $roleName): void
    {
        $teamHasUser = TeamHasUser::firstOrCreate([
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        TeamUserHasRole::firstOrCreate([
            'team_has_user_id' => $teamHasUser->id,
            'role_id'          => Role::where('name', $roleName)->firstOrFail()->id,
        ]);
    }
}
