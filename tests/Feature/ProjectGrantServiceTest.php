<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dataset;
use App\Models\ProjectGrant;
use App\Models\TeamHasUser;
use App\Models\ProjectGrantVersion;
use App\Models\ProjectGrantVersionHasDataset;
use App\Services\ProjectGrantService;

class ProjectGrantServiceTest extends TestCase
{
    private ProjectGrantService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectGrantService();
    }

    private function createProjectGrant(string $name, ?int $teamId = null, ?int $userId = null): ProjectGrant
    {
        $teamHasUser = TeamHasUser::query()->first();
        $teamId ??= $teamHasUser->team_id;
        $userId ??= $teamHasUser->user_id;

        $grant = ProjectGrant::create([
            'pid' => 'service-pid-' . uniqid(),
            'user_id' => $userId,
            'team_id' => $teamId,
        ]);

        ProjectGrantVersion::create([
            'project_grant_id' => $grant->id,
            'version' => 1,
            'project_grant_name' => $name,
        ]);

        return $grant;
    }

    public function test_list_filters_by_team_id(): void
    {
        $teamHasUser = TeamHasUser::query()->first();
        $matching = $this->createProjectGrant('Team Match', $teamHasUser->team_id);
        $this->createProjectGrant('Other Team');

        $results = $this->service->list(
            pid: null,
            version: null,
            projectGrantName: null,
            userId: null,
            teamId: $teamHasUser->team_id,
            withRelated: false,
            perPage: 50,
            sort: 'created_at:desc'
        );

        $ids = collect($results->items())->pluck('id');
        $this->assertTrue($ids->contains($matching->id));
        foreach ($results->items() as $grant) {
            $this->assertSame($teamHasUser->team_id, $grant->team_id);
        }
    }

    public function test_list_filters_by_project_grant_name(): void
    {
        $matching = $this->createProjectGrant('Unique Programme Alpha');
        $this->createProjectGrant('Something Else');

        $results = $this->service->list(
            pid: null,
            version: null,
            projectGrantName: 'Unique Programme',
            userId: null,
            teamId: null,
            withRelated: false,
            perPage: 50,
            sort: 'created_at:desc'
        );

        $ids = collect($results->items())->pluck('id');
        $this->assertTrue($ids->contains($matching->id));
    }

    public function test_list_filters_by_dataset_pid_and_version(): void
    {
        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $grant = $this->createProjectGrant('Dataset Linked Grant');
        ProjectGrantVersionHasDataset::create([
            'project_grant_id' => $grant->id,
            'dataset_id' => $dataset->id,
        ]);

        $results = $this->service->list(
            pid: $grant->pid,
            version: 1,
            projectGrantName: null,
            userId: null,
            teamId: null,
            withRelated: true,
            perPage: 50,
            sort: 'version:desc'
        );

        $this->assertCount(1, $results->items());
        $this->assertSame($grant->id, $results->items()[0]->id);
        $this->assertTrue($results->items()[0]->relationLoaded('versions'));
    }

    public function test_find_by_id_returns_grant_with_latest_version(): void
    {
        $grant = $this->createProjectGrant('Find By Id Grant');

        $found = $this->service->findById($grant->id, withRelated: false);

        $this->assertNotNull($found);
        $this->assertSame($grant->id, $found->id);
        $this->assertTrue($found->relationLoaded('latestVersion'));
        $this->assertSame('Find By Id Grant', $found->latestVersion->project_grant_name);
    }

    public function test_find_by_id_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->service->findById(999999999, withRelated: false));
    }
}
