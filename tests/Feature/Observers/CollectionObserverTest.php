<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Collection;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Observers\CollectionObserver;

use ElasticClientController as ECC;

class CollectionObserverTest extends TestCase
{
    
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
        Collection::flushEventListeners();

        $this->metadata = $this->getMetadata();

        $this->observer = Mockery::mock(CollectionObserver::class)->makePartial();
        app()->instance(CollectionObserver::class, $this->observer);

        Collection::observe(CollectionObserver::class);
    }

    public function testCollectionObserverCreatedEventIndexesActiveCollection()
    {
        $countInitialCollections = Collection::count();

        // Collection::observe(CollectionObserver::class);

        $this->observer->shouldReceive('indexElasticCollections')
            ->once()
            ->with($countInitialCollections + 1);

        $collection = Collection::factory()->create([
            'status' => Collection::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'status' => Collection::STATUS_ACTIVE,
        ]);
    }

    public function testCollectionObserverUpdatingEventSetsPrevStatus()
    {
        $collection = Collection::factory()->create([
            'status' => Collection::STATUS_ARCHIVED,
        ]);

        // Simulate updating event
        $collection->status = Collection::STATUS_ACTIVE;
        $this->observer->updating($collection);

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

        // Create a collection
        $collection = Collection::factory()->create();

        // Mock the observer's method
        $this->observer->shouldReceive('deleteCollectionFromElastic')->once()->with($collection->id);

        // Soft delete the collection and trigger the observer
        $collection->delete();

        // Verify the collection is soft-deleted
        $this->assertSoftDeleted('collections', ['id' => $collection->id]);
    }

}
