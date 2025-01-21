<?php

namespace Tests\Unit\Observers;

use Mockery;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Publication;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use App\Models\PublicationHasDatasetVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Observers\PublicationHasDatasetVersionObserver;

class PublicationHasDatasetVersionObserverTest extends TestCase
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
        Publication::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
            TeamHasUserSeeder::class,
            PublicationSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
        ]);
    }

    public function testPublicationHasDatasetVersionObserverCreatedEventTriggersElasticIndexing()
    {
        $observer = Mockery::mock(PublicationHasDatasetVersionObserver::class)->makePartial();
        $observer->shouldReceive('elasticPublicationHasDatasetVersion')->once();
        $this->app->instance(PublicationHasDatasetVersionObserver::class, $observer);

        $dataset = Dataset::where('status', Dataset::STATUS_ACTIVE)->first();
        $latestMetadata = $dataset->latestMetadata()->first();
        $datasetVersionId = $latestMetadata->id;

        $publication = Publication::where('status', Publication::STATUS_ACTIVE)->select('id')->first();

        $publicationHasDatasetVersion = PublicationHasDatasetVersion::create([
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
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

        $publication = Publication::where('status', Publication::STATUS_ACTIVE)->select('id')->first();

        $publicationHasDatasetVersion = PublicationHasDatasetVersion::create([
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
        ]);

        $publicationHasDatasetVersion->update(['link_type' => 'ABOUT']);

        $this->assertDatabaseHas('publication_has_dataset_version', [
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'ABOUT',
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

        $publication = Publication::where('status', Publication::STATUS_ACTIVE)->select('id')->first();

        $publicationHasDatasetVersion = PublicationHasDatasetVersion::create([
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
        ]);
        $publicationHasDatasetVersion->delete();

        // Assert: The elastic indexing method is called
        $this->assertSoftDeleted('publication_has_dataset_version', [
            'publication_id' => $publication->id,
            'dataset_version_id' => $datasetVersionId,
            'link_type' => 'USING',
        ]);
    }
}
