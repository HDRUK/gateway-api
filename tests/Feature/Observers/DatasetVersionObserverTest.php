<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\TeamHasUser;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Observers\DatasetVersionObserver;


class DatasetVersionObserverTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;
    protected $metadataAlt;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->metadata = $this->getMetadata();
        $this->metadataAlt = $this->metadata;
        $this->metadataAlt['metadata']['summary']['title'] = 'ABC title';

        $this->observer = Mockery::mock(DatasetVersionObserver::class)->makePartial();

    }

    public function testDatasetVersionObserverCreatedEventTriggersElasticDatasetVersion()
    {
        // Mock the elasticDatasetVersion method
        $this->observer->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class));

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

        $this->observer->created($datasetVersion);

        // Assertions
        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('dataset_versions', [
            'id' => $datasetVersion->id,
            'dataset_id' => $dataset->id,
        ]);
    }

    public function testDatasetVersionObserverUpdatedEventTriggersElasticDatasetVersion()
    {
        // Mock the elasticDatasetVersion method
        $this->observer->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class));

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
        $this->observer->updated($datasetVersion);

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
        // Mock the elasticDatasetVersion method
        $this->observer->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class));

        // Create a dataset and dataset version
        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
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

        DatasetVersion::where('id', $datasetVersion->id)->delete();

        // Manually trigger the deleted method on the observer
        $this->observer->deleted($datasetVersion);

        // Assertions
        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->assertSoftDeleted('dataset_versions', ['id' => $datasetVersion->id]);

        Mockery::close();
    }

}
