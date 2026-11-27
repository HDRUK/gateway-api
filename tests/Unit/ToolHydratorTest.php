<?php

namespace Tests\Unit;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Tool;
use App\Models\Team;
use App\Services\Search\ToolHydrator;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Tests\Traits\PipelineDatasets;

class ToolHydratorTest extends TestCase
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

    public function test_a_tool_search_result_names_its_datasets_by_their_current_short_title(): void
    {
        $dataset = $this->datasetSavedThroughThePipeline(Team::factory()->create()->id);
        $tool = Tool::factory()->create();
        $tool->versions()->attach($dataset->latestVersion(['id'])->id);

        $this->renameThroughThePipeline($dataset, 'Renamed title', 'Renamed short title');

        $results = app(ToolHydrator::class)->hydrate([['_id' => (string) $tool->id, '_source' => []]]);

        $this->assertSame(['Renamed short title'], $results[0]['datasets']);
    }
}
