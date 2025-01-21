<?php

namespace Tests\Unit\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Observers\CollectionObserver;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ElasticClientController as ECC;

class CollectionObserverTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $metadata;
    protected $observer;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();

        $this->observer = Mockery::mock(CollectionObserver::class)->makePartial();
        app()->instance(CollectionObserver::class, $this->observer);
    }

    public function testCollectionObserverCreatedEeventIndexesActiveCollection()
    {
        $this->observer->shouldReceive('indexElasticCollections')
            ->once()
            ->with(1);

        $collection = Collection::factory()->create([
            'id' => 1,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        $collection->observe(CollectionObserver::class);

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'status' => Collection::STATUS_ACTIVE,
        ]);
    }

    public function testCollectionObserverUpdatingEventSetsPrevStatus()
    {
        $observer = new CollectionObserver();

        $collection = Collection::factory()->create([
            'status' => Collection::STATUS_ARCHIVED,
        ]);

        // Simulate updating event
        $collection->status = Collection::STATUS_ACTIVE;
        $observer->updating($collection);

        // Assert the previous status is correctly set
        $this->assertEquals(Collection::STATUS_ARCHIVED, $collection->prevStatus);
    }

    public function testCollectionObserverUpdatedEventHandlesStatusChangesCorrectly()
    {
        ECC::shouldReceive("indexDocument")
            ->with(
                \Mockery::on(
                    function ($params) {
                        return $params['index'] === ECC::ELASTIC_NAME_COLLECTION;
                    }
                )
            )
            ->times(1);

        ECC::shouldIgnoreMissing();

        // Mock the observer
        $this->observer->shouldReceive('deleteCollectionFromElastic')
            ->with(1);

        $this->observer->shouldReceive('indexElasticCollections')
            ->with(2);

        // Create the collection
        $collection = Collection::factory()->create([
            'id' => 1,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        // Simulate a status change
        $collection->update(['status' => Collection::STATUS_ARCHIVED]);
        $collection->refresh();

        // Manually trigger the observer's updated method
        $this->observer->updated($collection);

        // Assert the correct status in the database
        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'status' => Collection::STATUS_ARCHIVED,
        ]);

        // Ensure the previous status is not active
        $this->assertNotEquals(Collection::STATUS_ACTIVE, $collection->status);
    }

    public function testCollectionObserverDeletedEventRemovesCollectionFromElastic()
    {
        ECC::shouldReceive("indexDocument")
        ->with(
            \Mockery::on(function ($params) {
                return $params['index'] === ECC::ELASTIC_NAME_COLLECTION;
            })
        )
        ->times(1);

        ECC::shouldIgnoreMissing();

        // Mock the observer's method
        $this->observer->shouldReceive('deleteCollectionFromElastic')->once()->with(1);

        // Create a collection
        $collection = Collection::factory()->create(['id' => 1]);

        // Soft delete the collection and trigger the observer
        $collection->delete();

        // Verify the collection is soft-deleted
        $this->assertSoftDeleted('collections', ['id' => $collection->id]);
    }

}
