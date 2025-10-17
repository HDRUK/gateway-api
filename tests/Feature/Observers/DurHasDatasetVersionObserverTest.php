<?php

namespace Tests\Feature\Observers;

use Mockery;
use App\Models\Dur;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Models\DurHasDatasetVersion;
use App\Models\CollectionHasDatasetVersion;
use App\Observers\DurHasDatasetVersionObserver;

class DurHasDatasetVersionObserverTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $userId;
    protected $teamId;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Collection::flushEventListeners();
        CollectionHasDatasetVersion::flushEventListeners();
        Dur::flushEventListeners();
        DurHasDatasetVersion::flushEventListeners();

        $this->observer = Mockery::mock(DurHasDatasetVersionObserver::class)->makePartial();
        app()->instance(DurHasDatasetVersionObserver::class, $this->observer);

        DurHasDatasetVersion::observe(DurHasDatasetVersionObserver::class);
    }

    public function testDurHasDatasetVersionObserverCallsElasticMethodOnCreatedEvent()
    {
        $this->observer
            ->shouldReceive('elasticDurHasDatasetVersion')
            ->once()
            ->andReturnTrue();

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

        $dur = Dur::where('status', Dur::STATUS_ACTIVE)->select('id')->first();

        DurHasDatasetVersion::create([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        $this->assertDatabaseHas('dur_has_dataset_version', [
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);
    }

    public function testDurHasDatasetVersionObserverElasticMethodOnUpdatedEvent()
    {
        $this->observer
            ->shouldReceive('elasticDurHasDatasetVersion')
            ->twice();

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

        $dur = Dur::where('status', Dur::STATUS_ACTIVE)->select('id')->first();

        $durHasDatasetVersion = DurHasDatasetVersion::create([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        $durHasDatasetVersion->update(['updated_at' => now()]);

        $this->assertDatabaseHas('dur_has_dataset_version', [
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);
    }

    public function testDurHasDatasetVersionObserverCallsElasticMethodOnDeletedEvent()
    {
        $this->observer->shouldReceive('elasticDurHasDatasetVersion')->once();

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

        $dur = Dur::where('status', Dur::STATUS_ACTIVE)->select('id')->first();

        DurHasDatasetVersion::create([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ]);

        DurHasDatasetVersion::where([
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
            'user_id' => $dur->user_id,
        ])->delete();

        // Assertions
        $this->assertSoftDeleted('dur_has_dataset_version', [
            'dur_id' => $dur->id,
            'dataset_version_id' => $datasetVersionId,
        ]);
    }
}
