<?php

namespace Tests\Feature\Observers;

use App\Jobs\IndexDataset;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\ProjectGrant;
use App\Models\TeamHasUser;
use App\Models\ProjectGrantVersion;
use App\Models\ProjectGrantVersionHasDataset;
use App\Observers\ProjectGrantObserver;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class ProjectGrantObserverTest extends TestCase
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
        ProjectGrantVersion::flushEventListeners();

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

    private function makeProjectGrantLinkedToDataset(Dataset $dataset): ProjectGrant
    {
        $grant = ProjectGrant::create([
            'pid' => 'observer-pid-' . uniqid(),
            'user_id' => $dataset->user_id,
            'team_id' => $dataset->team_id,
        ]);

        ProjectGrantVersionHasDataset::create([
            'project_grant_id' => $grant->id,
            'dataset_id' => $dataset->id,
        ]);

        return $grant;
    }

    public function test_created_dispatches_index_dataset_for_linked_active_datasets(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDataset();
        $grant = $this->makeProjectGrantLinkedToDataset($dataset);

        (new ProjectGrantObserver())->created($grant);

        Queue::assertPushed(IndexDataset::class, fn ($job) => true);
    }

    public function test_updated_dispatches_index_dataset_for_linked_active_datasets(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDataset();
        $grant = $this->makeProjectGrantLinkedToDataset($dataset);

        (new ProjectGrantObserver())->updated($grant);

        Queue::assertPushed(IndexDataset::class, fn ($job) => true);
    }

    public function test_deleted_dispatches_index_dataset_for_linked_active_datasets(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDataset();
        $grant = $this->makeProjectGrantLinkedToDataset($dataset);

        (new ProjectGrantObserver())->deleted($grant);

        Queue::assertPushed(IndexDataset::class, fn ($job) => true);
    }

    public function test_created_does_not_dispatch_when_no_linked_datasets(): void
    {
        Queue::fake();

        $teamHasUser = TeamHasUser::query()->first();
        $grant = ProjectGrant::create([
            'pid' => 'observer-pid-' . uniqid(),
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
        ]);

        ProjectGrantVersion::create([
            'project_grant_id' => $grant->id,
            'version' => 1,
            'project_grant_name' => 'Unlinked Grant',
        ]);

        (new ProjectGrantObserver())->created($grant);

        Queue::assertNotPushed(IndexDataset::class);
    }
}
