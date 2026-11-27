<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Services\DatasetReader;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Tests\Traits\PipelineDatasets;

class DatasetReaderTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }
    use PipelineDatasets;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_titles_come_from_the_latest_version_of_each_dataset(): void
    {
        $team = Team::factory()->create();
        $first = $this->datasetEditedThroughThePipeline($team->id, 'First edited title', 'First short');
        $second = $this->datasetEditedThroughThePipeline($team->id, 'Second edited title', 'Second short');

        $titles = app(DatasetReader::class)->titles([$first->id, $second->id]);

        $this->assertSame(['First edited title', 'First short'], [$titles[$first->id]->title, $titles[$first->id]->shortTitle]);
        $this->assertSame(['Second edited title', 'Second short'], [$titles[$second->id]->title, $titles[$second->id]->shortTitle]);
    }

    public function test_a_dataset_saved_without_a_title_has_no_title_rather_than_failing_the_batch(): void
    {
        $team = Team::factory()->create();
        $untitled = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $this->makeVersion($untitled, 1, null);
        $titled = $this->datasetEditedThroughThePipeline($team->id, 'A title', 'A short title');

        $titles = app(DatasetReader::class)->titles([$untitled->id, $titled->id]);

        $this->assertNull($titles[$untitled->id]->title);
        $this->assertSame('A title', $titles[$titled->id]->title);
    }

    public function test_asking_for_the_titles_of_an_unknown_dataset_is_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(DatasetReader::class)->titlesOf((int) Dataset::withTrashed()->max('id') + 1000);
    }

    public function test_titles_for_pids_returns_empty_array_for_empty_input(): void
    {
        $this->assertSame([], app(DatasetReader::class)->titlesForPids([]));
    }

    public function test_titles_for_pids_returns_title_for_a_known_pid(): void
    {
        $team    = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-KNOWN',
        ]);
        $this->makeVersion($dataset, 1, 'My Dataset Title');

        $result = app(DatasetReader::class)->titlesForPids(['PID-KNOWN']);

        $this->assertSame(['PID-KNOWN' => 'My Dataset Title'], $result);
    }

    public function test_titles_for_pids_returns_null_for_pid_with_no_dataset(): void
    {
        $result = app(DatasetReader::class)->titlesForPids(['PID-DOES-NOT-EXIST']);

        $this->assertArrayHasKey('PID-DOES-NOT-EXIST', $result);
        $this->assertNull($result['PID-DOES-NOT-EXIST']);
    }

    public function test_titles_for_pids_uses_the_latest_version_title_when_several_exist(): void
    {
        $team    = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-MULTI-VERSION',
        ]);
        $this->makeVersion($dataset, 1, 'Old Title');
        $this->makeVersion($dataset, 2, 'New Title');

        $result = app(DatasetReader::class)->titlesForPids(['PID-MULTI-VERSION']);

        $this->assertSame('New Title', $result['PID-MULTI-VERSION']);
    }

    public function test_titles_for_pids_returns_null_when_dataset_has_no_version_rows(): void
    {
        $team = Team::factory()->create();
        Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-NO-VERSION',
        ]);

        $result = app(DatasetReader::class)->titlesForPids(['PID-NO-VERSION']);

        $this->assertArrayHasKey('PID-NO-VERSION', $result);
        $this->assertNull($result['PID-NO-VERSION']);
    }

    public function test_titles_for_pids_returns_exactly_one_entry_per_input_pid(): void
    {
        $team    = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create([
            'status' => Dataset::STATUS_ACTIVE,
            'pid'    => 'PID-FOUND',
        ]);
        $this->makeVersion($dataset, 1, 'Found Title');

        $result = app(DatasetReader::class)->titlesForPids(['PID-FOUND', 'PID-MISSING']);

        $this->assertSame(['PID-FOUND', 'PID-MISSING'], array_keys($result));
        $this->assertSame('Found Title', $result['PID-FOUND']);
        $this->assertNull($result['PID-MISSING']);
    }

    private function makeVersion(Dataset $dataset, int $version, ?string $title): DatasetVersion
    {
        return DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => $version,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => config('metadata.GWDM.version'), 'metadata' => []],
            'title'       => $title,
            'short_title' => $title,
        ]);
    }
}
