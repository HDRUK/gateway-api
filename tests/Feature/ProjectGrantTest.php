<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\ProjectGrant;
use App\Models\Role;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use App\Models\ProjectGrantVersion;
use App\Models\ProjectGrantVersionHasDataset;
use Tests\Traits\MockExternalApis;

class ProjectGrantTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_INDEX = '/api/v1/project_grants';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function createProjectGrantWithVersion(?Dataset $dataset = null): ProjectGrant
    {
        $teamHasUser = TeamHasUser::query()->first();
        $dataset ??= Dataset::where('status', Dataset::STATUS_ACTIVE)->first();

        $grant = ProjectGrant::create([
            'pid' => 'grant-pid-' . uniqid(),
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
        ]);

        ProjectGrantVersion::create([
            'project_grant_id' => $grant->id,
            'version' => 1,
            'project_grant_name' => 'CRUK Research Programme',
            'lead_researcher' => 'Dr Test',
            'lead_research_institute' => 'Test Institute',
            'grant_numbers' => ['GRANT-001'],
            'project_grant_scope' => 'Cancer research',
        ]);

        if ($dataset) {
            ProjectGrantVersionHasDataset::create([
                'project_grant_id' => $grant->id,
                'dataset_id' => $dataset->id,
            ]);
        }

        return $grant->fresh(['latestVersion', 'datasets']);
    }

    public function test_index_returns_paginated_project_grants(): void
    {
        $grant = $this->createProjectGrantWithVersion();

        $response = $this->json('GET', self::TEST_URL_INDEX, [], ['Accept' => 'application/json']);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $response->assertJsonStructure([
            'current_page',
            'data' => [
                [
                    'id',
                    'pid',
                    'user_id',
                    'team_id',
                    'latest_version',
                ],
            ],
        ]);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($grant->id));
    }

    public function test_index_can_filter_by_pid(): void
    {
        $grant = $this->createProjectGrantWithVersion();

        $response = $this->json(
            'GET',
            self::TEST_URL_INDEX . '?pid=' . urlencode($grant->pid),
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($grant->id, $response->json('data.0.id'));
    }

    public function test_index_can_filter_by_project_grant_name(): void
    {
        $grant = $this->createProjectGrantWithVersion();

        $response = $this->json(
            'GET',
            self::TEST_URL_INDEX . '?projectGrantName=CRUK%20Research',
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($grant->id));
    }

    public function test_show_returns_project_grant(): void
    {
        $grant = $this->createProjectGrantWithVersion();

        $response = $this->json(
            'GET',
            self::TEST_URL_INDEX . '/' . $grant->id,
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $response->assertJsonPath('data.id', $grant->id);
        $response->assertJsonPath('data.pid', $grant->pid);
        $response->assertJsonPath('data.versions.0.projectGrantName', 'CRUK Research Programme');
    }

    public function test_show_returns_404_for_unknown_id(): void
    {
        $response = $this->json(
            'GET',
            self::TEST_URL_INDEX . '/999999999',
            [],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        $response->assertJson([
            'message' => 'not found',
            'data' => 'Not Found',
        ]);
    }

    public function test_store_creates_project_grant_with_version_and_dataset_links(): void
    {
        $teamHasUser = TeamHasUser::query()->first();
        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();

        $payload = [
            'pid' => 'api-grant-pid-' . uniqid(),
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'projectGrantName' => 'API Created Grant',
            'leadResearcher' => 'Dr API',
            'leadResearchInstitute' => 'API Institute',
            'grantNumbers' => ['API-001'],
            'projectGrantScope' => 'API Scope',
            'datasets' => [$dataset->id],
        ];

        $response = $this->json('POST', self::TEST_URL_INDEX, $payload, $this->header);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'created');
        $response->assertJsonPath('data.pid', $payload['pid']);
        // user_id is resolved from the authenticated JWT user, not the raw payload
        $response->assertJsonPath('data.user_id', (int) $this->currentUser['id']);
        $response->assertJsonPath('data.team_id', $payload['team_id']);
        $response->assertJsonPath('data.versions.0.projectGrantName', 'API Created Grant');

        $createdId = $response->json('data.id');
        $this->assertNotNull($createdId);

        $this->assertDatabaseHas('project_grants', [
            'id' => $createdId,
            'pid' => $payload['pid'],
            'user_id' => (int) $this->currentUser['id'],
            'team_id' => $payload['team_id'],
        ]);

        $this->assertDatabaseHas('project_grant_versions', [
            'project_grant_id' => $createdId,
            'version' => 1,
            'project_grant_name' => 'API Created Grant',
        ]);

        $this->assertDatabaseHas('project_grant_has_dataset', [
            'project_grant_id' => $createdId,
            'dataset_id' => $dataset->id,
        ]);
    }

    public function test_store_rejects_duplicate_pid(): void
    {
        $existing = $this->createProjectGrantWithVersion();
        $teamHasUser = TeamHasUser::query()->first();

        $payload = [
            'pid' => $existing->pid,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'projectGrantName' => 'Duplicate Pid Grant',
        ];

        $response = $this->json('POST', self::TEST_URL_INDEX, $payload, $this->header);

        $response->assertStatus(400);
        $this->assertDatabaseMissing('project_grant_versions', [
            'project_grant_name' => 'Duplicate Pid Grant',
        ]);
    }

    public function test_store_forbidden_without_project_grant_permission(): void
    {
        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $nonAdminUser = $this->getUserFromJwt($nonAdminJwt);
        $headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $teamHasUser = TeamHasUser::query()->first();

        $payload = [
            'pid' => 'no-perm-grant-pid-' . uniqid(),
            'user_id' => $nonAdminUser['id'],
            'team_id' => $teamHasUser->team_id,
            'projectGrantName' => 'No Permission Grant',
        ];

        $response = $this->json('POST', self::TEST_URL_INDEX, $payload, $headerNonAdmin);

        $response->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        $this->assertDatabaseMissing('project_grants', [
            'pid' => $payload['pid'],
        ]);
    }

    public function test_store_allows_team_member_with_project_grant_permission(): void
    {
        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $nonAdminUser = $this->getUserFromJwt($nonAdminJwt);
        $headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $teamId = TeamHasUser::query()->first()->team_id;
        $teamHasUser = TeamHasUser::firstOrCreate([
            'user_id' => $nonAdminUser['id'],
            'team_id' => $teamId,
        ]);

        // custodian.metadata.manager holds the project_grants.* permissions
        $role = Role::where('name', 'custodian.metadata.manager')->first();
        TeamUserHasRole::firstOrCreate([
            'team_has_user_id' => $teamHasUser->id,
            'role_id' => $role->id,
        ]);

        $payload = [
            'pid' => 'perm-grant-pid-' . uniqid(),
            'user_id' => $nonAdminUser['id'],
            'team_id' => $teamId,
            'projectGrantName' => 'Permitted Grant',
        ];

        $response = $this->json('POST', self::TEST_URL_INDEX, $payload, $headerNonAdmin);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_grants', [
            'pid' => $payload['pid'],
            'user_id' => (int) $nonAdminUser['id'],
            'team_id' => $teamId,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $teamHasUser = TeamHasUser::query()->first();

        $payload = [
            'pid' => 'unauth-grant-pid-' . uniqid(),
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'projectGrantName' => 'Unauthenticated Grant',
        ];

        $response = $this->json('POST', self::TEST_URL_INDEX, $payload, ['Accept' => 'application/json']);

        $response->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));
        $this->assertDatabaseMissing('project_grants', [
            'pid' => $payload['pid'],
        ]);
    }
}
