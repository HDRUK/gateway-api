<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\ProjectGrant;
use App\Models\TeamHasUser;
use App\Models\ProjectGrantVersion;
use App\Models\ProjectGrantVersionHasDataset;

class ProjectGrantTest extends TestCase
{
    public const TEST_URL_INDEX = '/api/v1/project_grants';

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

        $response = $this->json('POST', self::TEST_URL_INDEX, $payload, ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'created');
        $response->assertJsonPath('data.pid', $payload['pid']);
        $response->assertJsonPath('data.user_id', $payload['user_id']);
        $response->assertJsonPath('data.team_id', $payload['team_id']);
        $response->assertJsonPath('data.versions.0.projectGrantName', 'API Created Grant');

        $createdId = $response->json('data.id');
        $this->assertNotNull($createdId);

        $this->assertDatabaseHas('project_grants', [
            'id' => $createdId,
            'pid' => $payload['pid'],
            'user_id' => $payload['user_id'],
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
}
