<?php

namespace Tests\Feature\Observers;

use App\Jobs\DeindexDataset;
use App\Jobs\IndexDataset;
use App\Jobs\ReindexDataset;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\TeamHasUser;
use App\Observers\DatasetObserver;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class DatasetObserverTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();
        DatasetVersion::flushEventListeners();
        $this->metadata = $this->getMetadata();
    }

    private function makeActiveDatasetWithVersion(): Dataset
    {
        $teamHasUser = TeamHasUser::all()->random();
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

    public function testDatasetObserverDispatchesIndexDatasetOnCreatedEventIfActiveAndHasVersion(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDatasetWithVersion();

        (new DatasetObserver())->created($dataset);

        Queue::assertPushed(IndexDataset::class, fn ($job) => true);
    }

    public function testDatasetObserverDoesNotDispatchIndexDatasetOnCreatedEventIfNoVersion(): void
    {
        Queue::fake();

        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        (new DatasetObserver())->created($dataset);

        Queue::assertNotPushed(IndexDataset::class);
    }

    public function testDatasetObserverSetsPreviousStatusOnUpdatingEvent(): void
    {
        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_DRAFT,
        ]);

        $dataset->status = Dataset::STATUS_ACTIVE;
        (new DatasetObserver())->updating($dataset);

        $this->assertEquals(Dataset::STATUS_DRAFT, $dataset->prevStatus);
    }

    public function testDatasetObserverDispatchesDeindexDatasetWhenDatasetBecomesInactive(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDatasetWithVersion();
        $dataset->prevStatus = Dataset::STATUS_ACTIVE;
        $dataset->status = Dataset::STATUS_ARCHIVED;

        (new DatasetObserver())->updated($dataset);

        Queue::assertPushed(DeindexDataset::class);
        Queue::assertNotPushed(IndexDataset::class);
    }

    public function testDatasetObserverDispatchesReindexDatasetWhenActiveDatasetIsUpdated(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDatasetWithVersion();
        $dataset->prevStatus = Dataset::STATUS_ACTIVE;

        (new DatasetObserver())->updated($dataset);

        Queue::assertPushed(ReindexDataset::class);
    }

    public function testDatasetObserverDispatchesDeindexDatasetOnDeletedEventWhenPreviouslyActive(): void
    {
        Queue::fake();

        $dataset = $this->makeActiveDatasetWithVersion();
        $dataset->prevStatus = Dataset::STATUS_ACTIVE;
        $dataset->delete();

        $deleted = Dataset::withTrashed()->find($dataset->id);
        $deleted->prevStatus = Dataset::STATUS_ACTIVE;

        (new DatasetObserver())->deleted($deleted);

        Queue::assertPushed(DeindexDataset::class);
    }

    public function testDatasetObserverDoesNotDispatchDeindexDatasetOnDeletedEventWhenNotPreviouslyActive(): void
    {
        Queue::fake();

        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_DRAFT,
        ]);
        $dataset->delete();

        $deleted = Dataset::withTrashed()->find($dataset->id);
        $deleted->prevStatus = Dataset::STATUS_DRAFT;

        (new DatasetObserver())->deleted($deleted);

        Queue::assertNotPushed(DeindexDataset::class);
    }
}
