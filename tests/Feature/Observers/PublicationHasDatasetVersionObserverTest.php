<?php

namespace Tests\Feature\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use App\Models\PublicationHasDatasetVersion;

use App\Observers\PublicationHasDatasetVersionObserver;

class PublicationHasDatasetVersionObserverTest extends TestCase
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
    }

    public function testPublicationHasDatasetVersionObserverCreatedEventTriggersElasticIndexing()
    {
        $observer = Mockery::mock(PublicationHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticPublicationHasDatasetVersion')->once();
        $this->app->instance(PublicationHasDatasetVersionObserver::class, $observer);

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

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

        $publicationHasDatasetVersion = PublicationHasDatasetVersion::create([
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
            'description' => 'Extracted from Publication',
        ]);

        $this->assertDatabaseHas('publication_has_dataset_version', [
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
        ]);
        $this->assertNotNull($publicationHasDatasetVersion);
    }

    public function testPublicationHasDatasetVersionObserverUpdatedEventTriggersElasticIndexing()
    {
        $observer = Mockery::mock(PublicationHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticPublicationHasDatasetVersion')->twice();
        $this->app->instance(PublicationHasDatasetVersionObserver::class, $observer);

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

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

        $publicationHasDatasetVersion = PublicationHasDatasetVersion::create([
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
            'description' => 'Extracted from Publication',
        ]);

        $publicationHasDatasetVersion->update(['link_type' => 'ABOUT']);

        $this->assertDatabaseHas('publication_has_dataset_version', [
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'ABOUT',
            'description' => 'Extracted from Publication',
        ]);
        $this->assertEquals('ABOUT', $publicationHasDatasetVersion->link_type);
    }

    public function testPublicationHasDatasetVersionObserverDeletedEventTriggersElasticIndexing()
    {
        $observer = Mockery::mock(PublicationHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticPublicationHasDatasetVersion')->twice();
        $this->app->instance(PublicationHasDatasetVersionObserver::class, $observer);

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

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

        $publicationHasDatasetVersion = PublicationHasDatasetVersion::create([
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
            'description' => 'Extracted from Publication',
        ]);
        $publicationHasDatasetVersion->delete();

        // Assert: The elastic indexing method is called
        $this->assertSoftDeleted('publication_has_dataset_version', [
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
            'description' => 'Extracted from Publication',
        ]);
    }
}
