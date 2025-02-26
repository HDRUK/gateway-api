<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Observers\PublicationObserver;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicationObserverTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);
    }

    public function testPublicationObserverCreatedEventIndexesPublicationIfActive()
    {
        $observer = Mockery::mock(PublicationObserver::class)->makePartial();
        app()->instance(PublicationObserver::class, $observer);
        $observer->shouldReceive('indexElasticPublication')->once()->with(1);

        $publication = Publication::create([
            'id' => 1,
            'paper_title' => fake()->words(5, true),
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'year_of_publication' => fake()->year(),
            'paper_doi' => '10.1000/182',
            'publication_type' => fake()->words(1, true),
            'publication_type_mk1' => fake()->words(4, true),
            'journal_name' => fake()->sentence(),
            'abstract' => fake()->paragraph(),
            'url' => fake()->url(),
            'status' => Publication::STATUS_ACTIVE,
        ]);

        $this->assertEquals(Publication::STATUS_ACTIVE, $publication->status);
    }

    public function testPublicationObserverCreatedEventDoesNotIndexPublicationIfNotActive()
    {
        $observer = Mockery::mock(PublicationObserver::class)->makePartial();
        app()->instance(PublicationObserver::class, $observer);
        $observer->shouldNotReceive('indexElasticPublication');

        $publication = Publication::create([
            'id' => 1,
            'paper_title' => fake()->words(5, true),
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'year_of_publication' => fake()->year(),
            'paper_doi' => '10.1000/182',
            'publication_type' => fake()->words(1, true),
            'publication_type_mk1' => fake()->words(4, true),
            'journal_name' => fake()->sentence(),
            'abstract' => fake()->paragraph(),
            'url' => fake()->url(),
            'status' => Publication::STATUS_DRAFT,
        ]);

        $this->assertEquals(Publication::STATUS_DRAFT, $publication->status);
    }

    public function testPublicationObserverUpdatingEventSetsPrevStatus()
    {
        $observer = Mockery::mock(PublicationObserver::class)->makePartial();
        app()->instance(PublicationObserver::class, $observer);
        $observer->shouldReceive('indexElasticPublication')->with(1);

        $publication = Publication::create([
            'id' => 1,
            'paper_title' => fake()->words(5, true),
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'year_of_publication' => fake()->year(),
            'paper_doi' => '10.1000/182',
            'publication_type' => fake()->words(1, true),
            'publication_type_mk1' => fake()->words(4, true),
            'journal_name' => fake()->sentence(),
            'abstract' => fake()->paragraph(),
            'url' => fake()->url(),
            'status' => Publication::STATUS_DRAFT,
        ]);

        $observer = new PublicationObserver();
        $publication->status = Publication::STATUS_ACTIVE;
        $observer->updating($publication);

        $this->assertEquals(Dataset::STATUS_DRAFT, $publication->prevStatus);
    }

    public function testPublicationObserverDeletedEventRemovesFromElasticIfStatusWasActive()
    {
        $observer = Mockery::mock(PublicationObserver::class)->makePartial();
        app()->instance(PublicationObserver::class, $observer);
        $observer->shouldReceive('indexElasticPublication')->with(1);
        $observer->shouldReceive('deletePublicationFromElastic')->once()->with(1);

        $publication = Publication::create([
            'id' => 1,
            'paper_title' => fake()->words(5, true),
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'year_of_publication' => fake()->year(),
            'paper_doi' => '10.1000/182',
            'publication_type' => fake()->words(1, true),
            'publication_type_mk1' => fake()->words(4, true),
            'journal_name' => fake()->sentence(),
            'abstract' => fake()->paragraph(),
            'url' => fake()->url(),
            'status' => Publication::STATUS_DRAFT,
        ]);

        $publication->prevStatus = Publication::STATUS_ACTIVE;
        $publication->status = Publication::STATUS_ARCHIVED;

        $observer->updated($publication);

        $this->assertEquals(Dataset::STATUS_ARCHIVED, $publication->status);
    }

    public function testPublicationObserverDeletedEventDoesNotRemoveFromElasticIfStatusWasNotActive()
    {
        $observer = Mockery::mock(PublicationObserver::class)->makePartial();
        app()->instance(PublicationObserver::class, $observer);
        $observer->shouldReceive('deletePublicationFromElastic')->with(1);

        $publication = Publication::create([
            'id' => 1,
            'paper_title' => fake()->words(5, true),
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'year_of_publication' => fake()->year(),
            'paper_doi' => '10.1000/182',
            'publication_type' => fake()->words(1, true),
            'publication_type_mk1' => fake()->words(4, true),
            'journal_name' => fake()->sentence(),
            'abstract' => fake()->paragraph(),
            'url' => fake()->url(),
            'status' => Publication::STATUS_ARCHIVED,
        ]);

        $observer->created($publication);

        $this->assertDatabaseHas('publications', [
            'id' => 1,
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'paper_doi' => '10.1000/182',
            'status' => Publication::STATUS_ARCHIVED,
        ]);

        Publication::where('id', 1)->delete();
        $publication = Publication::where('id', 1)->withTrashed()->first();

        $observer->deleted($publication);

        $this->assertSoftDeleted('publications', ['id' => 1]);
    }
}
