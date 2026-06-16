<?php

namespace Tests\Feature\Observers;

use App\Jobs\IndexDataset;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\ProjectGrant;
use App\Models\TeamHasUser;
use App\Models\ProjectGrantVersionHasDataset;
use App\Observers\ProjectGrantHasDatasetVersionObserver;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class ProjectGrantHasDatasetVersionObserverTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        ProjectGrant::flushEventListeners();

        $this->metadata = $this->getMetadata();
    }

    private function makeActiveDataset(): Dataset
    {
        $teamHasUser = TeamHasUser::query()->first();
        $dataset = Dataset::create([
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'provider_team_id' => $dataset->team_id,
            'version' => 1,
            'metadata' => $this->metadata,
        ]);

        return $dataset;
    }

    public function test_created_dispatches_index_dataset_for_active_dataset(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDataset();
        $grant = ProjectGrant::create([
            'pid' => 'pivot-pid-' . uniqid(),
            'user_id' => $dataset->user_id,
            'team_id' => $dataset->team_id,
        ]);

        $pivot = ProjectGrantVersionHasDataset::create([
            'project_grant_id' => $grant->id,
            'dataset_id' => $dataset->id,
        ]);

        (new ProjectGrantHasDatasetVersionObserver())->created($pivot);

        Queue::assertPushed(IndexDataset::class, fn ($job) => true);
    }

    public function test_deleted_dispatches_index_dataset_for_active_dataset(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDataset();
        $grant = ProjectGrant::create([
            'pid' => 'pivot-pid-' . uniqid(),
            'user_id' => $dataset->user_id,
            'team_id' => $dataset->team_id,
        ]);

        $pivot = ProjectGrantVersionHasDataset::create([
            'project_grant_id' => $grant->id,
            'dataset_id' => $dataset->id,
        ]);

        (new ProjectGrantHasDatasetVersionObserver())->deleted($pivot);

        Queue::assertPushed(IndexDataset::class, fn ($job) => true);
    }

    public function test_created_does_not_dispatch_for_inactive_dataset(): void
    {
        Queue::fake();

        $teamHasUser = TeamHasUser::query()->first();
        $dataset = Dataset::create([
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_DRAFT,
        ]);

        $grant = ProjectGrant::create([
            'pid' => 'pivot-pid-' . uniqid(),
            'user_id' => $dataset->user_id,
            'team_id' => $dataset->team_id,
        ]);

        $pivot = ProjectGrantVersionHasDataset::create([
            'project_grant_id' => $grant->id,
            'dataset_id' => $dataset->id,
        ]);

        (new ProjectGrantHasDatasetVersionObserver())->created($pivot);

        Queue::assertNotPushed(IndexDataset::class);
    }
}
