<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\TeamHasUser;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use Database\Seeders\MinimalUserSeeder;
use App\Observers\DatasetVersionObserver;
use Database\Seeders\SpatialCoverageSeeder;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class DatasetVersionObserverTest extends TestCase
{
    use FastRefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;
    protected $metadataAlt;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
        $this->metadataAlt = $this->metadata;
        $this->metadataAlt['metadata']['summary']['title'] = 'ABC title';
    }

    public function testDatasetVersionObserverCreatedEventTriggersElasticDatasetVersion()
    {
        // Create a mock for the observer
        $observerMock = Mockery::mock(DatasetVersionObserver::class)->makePartial();

        // Mock the elasticDatasetVersion method
        $observerMock->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class));


        // Detach any default observers to prevent conflicts
        DatasetVersion::flushEventListeners();

        // Create a dataset and dataset version
        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $datasetVersion = DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'provider_team_id' => $dataset->team_id,
            'version' => 1,
            'metadata' => $this->metadata,
        ]);

        $observerMock->created($datasetVersion);

        // Assertions
        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('dataset_versions', [
            'id' => $datasetVersion->id,
            'dataset_id' => $dataset->id,
        ]);

        Mockery::close();
    }

    public function testDatasetVersionObserverUpdatedEventTriggersElasticDatasetVersion()
    {
        // Create a mock for the observer
        $observerMock = Mockery::mock(DatasetVersionObserver::class)->makePartial();

        // Mock the elasticDatasetVersion method
        $observerMock->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class));

        // Detach any default observers to prevent conflicts
        DatasetVersion::flushEventListeners();

        // Create a dataset and dataset version
        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $datasetVersion = DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'provider_team_id' => $dataset->team_id,
            'version' => 1,
            'metadata' => $this->metadata,
        ]);

        // Simulate an update to the dataset version
        $datasetVersion->metadata = $this->metadataAlt;
        $datasetVersion->save();

        // Manually trigger the updated method on the observer
        $observerMock->updated($datasetVersion);

        // Assertions
        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('dataset_versions', [
            'id' => $datasetVersion->id,
            'dataset_id' => $dataset->id,
            'version' => 1,
        ]);

        Mockery::close();
    }

    public function testDatasetVersionObserverDeletedEventTriggersElasticDatasetVersion()
    {
        // Create a mock for the observer
        $observerMock = Mockery::mock(DatasetVersionObserver::class)->makePartial();

        // Mock the elasticDatasetVersion method
        $observerMock->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class));

        // Detach any default observers to prevent conflicts
        DatasetVersion::flushEventListeners();

        // Create a dataset and dataset version
        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $datasetVersion = DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'provider_team_id' => $dataset->team_id,
            'version' => 1,
            'metadata' => $this->metadata,
        ]);

        DatasetVersion::where('id', 1)->delete();

        // Manually trigger the deleted method on the observer
        $observerMock->deleted($datasetVersion);

        // Assertions
        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->assertSoftDeleted('dataset_versions', ['id' => $datasetVersion->id]);

        Mockery::close();
    }

}
