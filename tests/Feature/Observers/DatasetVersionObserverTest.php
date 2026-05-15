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
    protected $observer;

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
        $this->observer->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class), 'created');

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
        $this->observer->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class), 'updated');

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

        $datasetVersion->metadata = $this->metadataAlt;
        $datasetVersion->save();

        $this->observer->updated($datasetVersion);

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
        $this->observer->shouldReceive('elasticDatasetVersion')
            ->once()
            ->with(Mockery::type(DatasetVersion::class), 'deleted');

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

        $this->observer->deleted($datasetVersion);

        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->assertSoftDeleted('dataset_versions', ['id' => $datasetVersion->id]);

        Mockery::close();
    }
}
