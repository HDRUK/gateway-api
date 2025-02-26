<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\TeamHasUser;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use Database\Seeders\MinimalUserSeeder;
use App\Models\CollectionHasDatasetVersion;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Observers\CollectionHasDatasetVersionObserver;
use Config;

class CollectionHasDatasetVersionObserverTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;
    protected $metadataSecond;
    protected $observer;
    protected $datasetNew;
    protected $datasetVersionNew;
    protected $datasetSecond;
    protected $datasetVersionSecond;
    protected $teamHasUser;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Collection::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
        $this->metadataSecond = $this->metadata;
        $this->metadataSecond['metadata']['summary']['title'] = 'ABC title';

        $this->teamHasUser = TeamHasUser::all()->random();
        $this->datasetNew = Dataset::create([
            'id' => 1,
            'user_id' => $this->teamHasUser->user_id,
            'team_id' => $this->teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->datasetVersionNew = DatasetVersion::create([
            'dataset_id' => $this->datasetNew->id,
            'provider_team_id' => $this->datasetNew->team_id,
            'version' => 1,
            'metadata' => $this->metadata,
        ]);

        $this->datasetSecond = Dataset::create([
            'id' => 2,
            'user_id' => $this->teamHasUser->user_id,
            'team_id' => $this->teamHasUser->team_id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $this->datasetVersionSecond = DatasetVersion::create([
            'dataset_id' => $this->datasetSecond->id,
            'provider_team_id' => $this->datasetSecond->team_id,
            'version' => 1,
            'metadata' => $this->metadataSecond,
        ]);

    }

    public function testCollectionHasDatasetVersionObserverCreatedEventTriggersElasticIndexing()
    {
        $collection = Collection::create([
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => $this->teamHasUser->team_id,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        $observer = Mockery::mock(CollectionHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticCollectionHasDatasetVersion')->once();

        app()->instance(CollectionHasDatasetVersionObserver::class, $observer);

        $collectionHasDatasetVersion = CollectionHasDatasetVersion::create([
            'collection_id' => $collection->id,
            'dataset_version_id' => $this->datasetVersionNew->id,
        ]);

        $this->assertNotNull($collectionHasDatasetVersion);
    }

    public function testCollectionHasDatasetVersionObserverUpdatedEventTriggersElasticIndexing()
    {
        $observer = Mockery::mock(CollectionHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticCollectionHasDatasetVersion')->once();

        // Register the mocked observer
        app()->instance(CollectionHasDatasetVersionObserver::class, $observer);

        // Create a Collection instance
        $collection = Collection::create([
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => $this->teamHasUser->team_id,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        // Flush and re-register the event listeners for the test
        CollectionHasDatasetVersion::flushEventListeners();
        CollectionHasDatasetVersion::observe(CollectionHasDatasetVersionObserver::class);

        // Create a CollectionHasDatasetVersion instance
        $collectionHasDatasetVersion = CollectionHasDatasetVersion::create([
            'collection_id' => $collection->id,
            'dataset_version_id' => $this->datasetVersionNew->id,
        ]);

        // Ensure no call occurs during creation
        Mockery::close(); // Reset any previous expectations

        // Re-register the observer with fresh expectations
        $observer = Mockery::mock(CollectionHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticCollectionHasDatasetVersion')->once();
        app()->instance(CollectionHasDatasetVersionObserver::class, $observer);

        // Perform the update
        $collectionHasDatasetVersion->update([
            'dataset_version_id' => $this->datasetVersionSecond->id,
        ]);

        // Assert the field was updated
        $this->assertEquals($this->datasetVersionSecond->id, $collectionHasDatasetVersion->dataset_version_id);

        // Close Mockery expectations
        Mockery::close();
    }

    public function testCollectionHasDatasetVersionObserverDeletedEventTriggersElasticIndexing()
    {
        // Mock the observer
        $observer = Mockery::mock(CollectionHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticCollectionHasDatasetVersion')->once();

        // Register the mocked observer
        app()->instance(CollectionHasDatasetVersionObserver::class, $observer);

        // Flush and re-register the event listeners
        CollectionHasDatasetVersion::flushEventListeners();
        CollectionHasDatasetVersion::observe(CollectionHasDatasetVersionObserver::class);

        // Create related models
        $collection = Collection::factory()->create(['status' => Collection::STATUS_ACTIVE]);
        CollectionHasDatasetVersion::create([
            'collection_id' => $collection->id,
            'dataset_version_id' => $this->datasetVersionNew->id,
        ]);

        // Perform the delete operation
        CollectionHasDatasetVersion::where([
            'collection_id' => $collection->id,
            'dataset_version_id' => $this->datasetVersionNew->id,
        ])->delete();

        // Assert that the model is soft-deleted
        $this->assertSoftDeleted('collection_has_dataset_version', [
            'collection_id' => $collection->id,
            'dataset_version_id' => $this->datasetVersionNew->id,
        ]);

        // Verify Mockery expectations
        Mockery::close();
    }

}
