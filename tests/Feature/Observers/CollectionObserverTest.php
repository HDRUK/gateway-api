<?php

namespace Tests\Unit\Observers;

use Config;
use Mockery;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Collection;
use App\Observers\CollectionObserver;
use Database\Seeders\MinimalUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CollectionObserverTest extends TestCase
{
    use RefreshDatabase;

    protected $observer;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MinimalUserSeeder::class,
        ]);

        $this->observer = Mockery::mock(CollectionObserver::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testCreatedEvent()
    {
        $collection = Collection::create([
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => Team::all()->random()->id,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        $this->observer
            ->shouldReceive('indexElasticCollections')
            ->once()
            ->with($collection->id);

        $this->observer->created($collection);

        $this->assertTrue(true, 'indexElasticCollections was called for STATUS_ACTIVE');
    }

    public function testCreatedEventDoesNotTriggerIndexingForInactiveStatus()
    {
        $collection = Collection::create([
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => Team::all()->random()->id,
            'status' => Collection::STATUS_DRAFT,
        ]);

        $this->observer->shouldNotReceive('indexElasticCollections');

        $this->observer->created($collection);

        $this->assertTrue(true, 'indexElasticCollections was not called for non-active status');
    }

    public function testUpdatedEventTriggersIndexingForActiveStatus()
    {
        $collection = Collection::create([
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => Team::all()->random()->id,
            'status' => Collection::STATUS_DRAFT,
        ]);

        $collection->status = Collection::STATUS_ACTIVE;
        $collection->save(); // Save to trigger the 'updated' event

        $this->observer
            ->shouldReceive('indexElasticCollections')
            ->once()
            ->with($collection->id);

        $this->observer->updated($collection);

        $this->assertEquals(Collection::STATUS_ACTIVE, $collection->status);
        $this->assertTrue(true, 'indexElasticCollections was called for STATUS_ACTIVE on update');
    }

    public function testUpdatedEventTriggersDeletionForNonActiveStatus()
    {
        $collection = Collection::create([
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => Team::all()->random()->id,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        $collection->status = Collection::STATUS_DRAFT;
        $collection->save(); // Save to trigger the 'updated' event

        $this->observer
            ->shouldReceive('deleteCollectionFromElastic')
            ->once()
            ->with($collection->id);

        $this->observer->updated($collection);

        $this->assertEquals('DRAFT', $collection->status, 'The collection status should be non-active');
        $this->assertTrue(true, 'deleteCollectionFromElastic was called for non-active status on update');
    }

    public function testDeletedEvent()
    {
        $collection = Collection::create([
            'name' => fake()->unique()->word,
            'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
            'image_link' => Config::get('services.media.base_url') . '/collections/' . fake()->lexify('????_????_????.') . fake()->randomElement(['jpg', 'jpeg', 'png', 'gif']),
            'enabled' => fake()->randomElement([0, 1]),
            'public' => fake()->randomElement([0, 1]),
            'counter' => fake()->randomNumber(5, true),
            'team_id' => Team::all()->random()->id,
            'status' => Collection::STATUS_ACTIVE,
        ]);

        $this->observer
            ->shouldReceive('deleteCollectionFromElastic')
            ->once()
            ->with($collection->id);

        $collection->delete();

        $this->observer->deleted($collection);

        $this->assertSoftDeleted('collections', ['id' => $collection->id]);
        $this->assertTrue(true, 'deleteCollectionFromElastic was called on deletion');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
