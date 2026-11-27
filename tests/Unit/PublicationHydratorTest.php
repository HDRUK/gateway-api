<?php

namespace Tests\Unit;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\Team;
use App\Services\DatasetReader;
use App\Services\Search\PublicationHydrator;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Tests\Traits\PipelineDatasets;

class PublicationHydratorTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }
    use PipelineDatasets;

    protected function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_a_publication_search_result_names_its_datasets_by_their_current_short_title(): void
    {
        $dataset = $this->datasetSavedThroughThePipeline(Team::factory()->create()->id);
        $publication = Publication::factory()->create();
        $publication->versions()->attach($dataset->latestVersion(['id'])->id, ['link_type' => 'USING']);

        $this->renameThroughThePipeline($dataset, 'Renamed title', 'Renamed short title');

        $results = (new PublicationHydrator(new DatasetReader()))->hydrate([['_id' => (string) $publication->id, '_source' => []]]);

        $this->assertSame('Renamed short title', $results[0]['datasetVersions'][0]['name']);
    }
}
