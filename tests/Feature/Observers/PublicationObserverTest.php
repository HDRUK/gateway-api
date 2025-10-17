<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Observers\PublicationObserver;

class PublicationObserverTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Publication::flushEventListeners();

        $this->observer = Mockery::mock(PublicationObserver::class)->makePartial();
        app()->instance(PublicationObserver::class, $this->observer);

        Publication::observe(PublicationObserver::class);
    }

    public function testPublicationObserverCreatedEventIndexesPublicationIfActive()
    {
        $countInitialPublications = Publication::count();

        $this->observer->shouldReceive('indexElasticPublication')
            ->once()
            ->with($countInitialPublications + 1);

        $publication = Publication::create([
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
        $this->observer->shouldNotReceive('indexElasticPublication');

        $publication = Publication::create([
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
        $countInitialPublications = Publication::count();

        $this->observer->shouldReceive('indexElasticPublication')->with($countInitialPublications + 1);

        $publication = Publication::create([
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

        $publication->status = Publication::STATUS_ACTIVE;
        $this->observer->updating($publication);

        $this->assertEquals(Dataset::STATUS_DRAFT, $publication->prevStatus);
    }

    public function testPublicationObserverDeletedEventRemovesFromElasticIfStatusWasActive()
    {
        $countInitialPublications = Publication::count();

        $this->observer->shouldReceive('indexElasticPublication')->with($countInitialPublications + 1);
        $this->observer->shouldReceive('deletePublicationFromElastic')
            ->once()
            ->with($countInitialPublications + 1);

        $publication = Publication::create([
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

        $this->observer->updated($publication);

        $this->assertEquals(Dataset::STATUS_ARCHIVED, $publication->status);
    }

    public function testPublicationObserverDeletedEventDoesNotRemoveFromElasticIfStatusWasNotActive()
    {
        $countInitialPublications = Publication::count();
        $this->observer->shouldReceive('deletePublicationFromElastic')->with($countInitialPublications + 1);

        $publication = Publication::create([
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

        $this->observer->created($publication);

        $this->assertDatabaseHas('publications', [
            'authors' => 'Author One, Author Two, Author Three, Author Four',
            'paper_doi' => '10.1000/182',
            'status' => Publication::STATUS_ARCHIVED,
        ]);

        Publication::where('id', $publication->id)->delete();
        $publication = Publication::where('id', $publication->id)->withTrashed()->first();

        $this->observer->deleted($publication);

        $this->assertSoftDeleted('publications', ['id' => $publication->id]);
    }
}
