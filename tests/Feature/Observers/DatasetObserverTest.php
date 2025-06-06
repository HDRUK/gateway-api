<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\TeamHasUser;
use App\Models\DatasetVersion;
use App\Observers\DatasetObserver;
use Tests\Traits\MockExternalApis;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatasetObserverTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;
    protected $metadataAlt;

    public function setUp(): void
    {
        $this->commonSetUp();

        DatasetVersion::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
    }

    public function testDatasetObserverReindexesElasticOnCreatedEeventIfActiveAndHasVersion()
    {
        $observer = Mockery::mock(DatasetObserver::class)->makePartial();
        $observer->shouldReceive('reindexElastic')->with(2);

        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'id' => 1,
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

        $observer->created($dataset);

        $this->assertDatabaseHas('datasets', [
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('dataset_versions', ['dataset_id' => 1]);

    }

    public function testDatasetObserverSetsPreviousStatusOnUpdatingEvent()
    {
        $observer = Mockery::mock(DatasetObserver::class)->makePartial();
        $observer->shouldReceive('reindexElastic')->with(1);

        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_DRAFT,
        ]);

        $observer = new DatasetObserver();
        $dataset->status = Dataset::STATUS_ACTIVE;
        $observer->updating($dataset);

        $this->assertEquals(Dataset::STATUS_DRAFT, $dataset->prevStatus);
    }

    public function testDatasetObserverReindexesOrDeletesFromElasticOnUpdatedEventBasedOnStatusChange()
    {
        $observer = Mockery::mock(DatasetObserver::class)->makePartial();
        $observer->shouldReceive('reindexElastic')->with(1);
        $observer->shouldReceive('deleteDatasetFromElastic')->once()->with(1);

        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);

        $dataset->prevStatus = Dataset::STATUS_ACTIVE;
        $dataset->status = Dataset::STATUS_ARCHIVED;

        $observer->updated($dataset);

        $this->assertEquals(Dataset::STATUS_ARCHIVED, $dataset->status);
    }

    public function testDatasetObserverReindexesElasticOnDeletedEvent()
    {
        $observer = Mockery::mock(DatasetObserver::class)->makePartial();
        $observer->shouldReceive('reindexElastic')->with(1);

        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::create([
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        DatasetVersion::factory()->create(['dataset_id' => 1]);

        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'provider_team_id' => $dataset->team_id,
            'version' => 1,
            'metadata' => $this->metadata,
        ]);

        $observer->created($dataset);

        $this->assertDatabaseHas('datasets', [
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('dataset_versions', ['dataset_id' => 1]);

        Dataset::where('id', 1)->delete();
        $dataset = Dataset::where('id', 1)->withTrashed()->first();

        $observer->deleted($dataset);

        $this->assertDatabaseHas('datasets', [
            'id' => 1,
            'user_id' => $teamHasUser->user_id,
            'team_id' => $teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);
        $this->assertSoftDeleted('datasets', ['id' => 1]);
    }
}
