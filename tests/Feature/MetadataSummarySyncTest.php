<?php

namespace Tests\Feature;

use App\Jobs\SyncDatasetMetadataSummary;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\Search\MetadataSummaryIndex;
use App\Services\TypesenseService;
use Database\Seeders\PrukCrocodSampleSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class MetadataSummarySyncTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    // A PRUK sample dataset.
    private const PID = '4733b419-6cb8-5178-9ebc-95ae4e4e6858';

    private MockInterface $writer;

    private array $imported = [];

    protected function setUp(): void
    {
        $this->commonSetUp();
        $this->seed(PrukCrocodSampleSeeder::class);

        config(['typesense.summary_embedding_config.partners' => '']);

        // The timed client: records every document sent to Typesense.
        $this->writer = Mockery::mock(TypesenseService::class);
        $this->writer->shouldReceive('importDocuments')->andReturnUsing(function (string $collection, array $batch) {
            array_push($this->imported, ...$batch);

            return array_fill(0, count($batch), ['success' => true]);
        });
    }

    private function index(bool $collectionExists = true): MetadataSummaryIndex
    {
        $typesense = Mockery::mock(TypesenseService::class);
        $typesense->shouldReceive('withTimeout')->andReturn($this->writer);
        $typesense->shouldReceive('collectionExists')->andReturn($collectionExists);
        if ($collectionExists) {
            $typesense->shouldNotReceive('createCollection');
        } else {
            $typesense->shouldReceive('createCollection')->once();
        }

        return new MetadataSummaryIndex($typesense);
    }

    private function dataset(): Dataset
    {
        return Dataset::where('pid', self::PID)->firstOrFail();
    }

   
    public function test_sync_replaces_the_documents_of_a_searchable_dataset(): void
    {
        $dataset = $this->dataset();
        $this->writer->shouldReceive('deleteDocumentsWhere')
            ->once()
            ->with(MetadataSummaryIndex::collectionName(), 'dataset_id:='.$dataset->id);

        $count = $this->index()->syncDataset($dataset->id);

        $this->assertGreaterThan(0, $count);
        $this->assertCount($count, $this->imported);
        $this->assertSame([(string) $dataset->id], array_values(array_unique(array_column($this->imported, 'dataset_id'))));
        $this->assertContains("{$dataset->id}-title", array_column($this->imported, 'id'));
    }

    public function test_sync_indexes_any_partner_when_no_partners_are_listed(): void
    {
        $dataset = $this->dataset();
        $dataset->update(['partner_context' => 'HDRUK']);
        $this->writer->shouldReceive('deleteDocumentsWhere')->once();

        $this->assertGreaterThan(0, $this->index()->syncDataset($dataset->id));
        $this->assertSame(['HDRUK'], array_values(array_unique(array_column($this->imported, 'partnerContext'))));
    }

    public function test_sync_only_removes_a_dataset_of_an_unlisted_partner(): void
    {
        config(['typesense.summary_embedding_config.partners' => 'CRUK']);
        $dataset = $this->dataset();
        $this->writer->shouldReceive('deleteDocumentsWhere')->once()->with(MetadataSummaryIndex::collectionName(), 'dataset_id:='.$dataset->id);

        $this->assertSame(0, $this->index()->syncDataset($dataset->id));
        $this->assertSame([], $this->imported);
    }

    public function test_sync_only_removes_a_draft_dataset(): void
    {
        $dataset = $this->dataset();
        Dataset::withoutEvents(fn () => $dataset->update(['status' => Dataset::STATUS_DRAFT]));
        $this->writer->shouldReceive('deleteDocumentsWhere')->once();

        $this->assertSame(0, $this->index()->syncDataset($dataset->id));
        $this->assertSame([], $this->imported);
    }

    public function test_sync_only_removes_a_deleted_dataset(): void
    {
        $dataset = $this->dataset();
        Dataset::withoutEvents(fn () => $dataset->delete());
        $this->writer->shouldReceive('deleteDocumentsWhere')->once()->with(MetadataSummaryIndex::collectionName(), 'dataset_id:='.$dataset->id);

        $this->assertSame(0, $this->index()->syncDataset($dataset->id));
        $this->assertSame([], $this->imported);
    }

    public function test_sync_creates_the_collection_when_it_is_missing(): void
    {
        $this->writer->shouldReceive('deleteDocumentsWhere')->once();

        // index(collectionExists: false) expects createCollection exactly once.
        $this->index(collectionExists: false)->syncDataset($this->dataset()->id);
    }

    // --- The job ----------------------------------------------------------

    public function test_the_job_does_nothing_while_the_index_is_disabled(): void
    {
        config(['scout.driver' => 'typesense', 'typesense.summary_embedding_config.enabled' => false]);
        $index = Mockery::mock(MetadataSummaryIndex::class);
        $index->shouldNotReceive('syncDataset');

        (new SyncDatasetMetadataSummary(7))->handle($index);
    }

    public function test_the_job_syncs_the_dataset_when_enabled(): void
    {
        config(['scout.driver' => 'typesense', 'typesense.summary_embedding_config.enabled' => true]);
        $index = Mockery::mock(MetadataSummaryIndex::class);
        $index->shouldReceive('syncDataset')->once()->with(7)->andReturn(3);

        (new SyncDatasetMetadataSummary(7))->handle($index);
    }

    public function test_a_failing_sync_is_logged_not_thrown(): void
    {
        config(['scout.driver' => 'typesense', 'typesense.summary_embedding_config.enabled' => true]);
        $index = Mockery::mock(MetadataSummaryIndex::class);
        $index->shouldReceive('syncDataset')->andThrow(new \RuntimeException('Typesense is down'));
        Log::shouldReceive('warning')->once()->with('SyncDatasetMetadataSummary failed', [
            'dataset_id' => 7,
            'error' => 'Typesense is down',
        ]);

        (new SyncDatasetMetadataSummary(7))->handle($index);
    }

    // --- The observer -----------------------------------------------------

    private function pushedFor(int $datasetId): \Closure
    {
        // The job's dataset id is private; read it from inside the job.
        return fn (SyncDatasetMetadataSummary $job) => (fn () => $this->datasetId)->call($job) === $datasetId;
    }

    public function test_saving_a_version_queues_a_sync_for_its_dataset(): void
    {
        Queue::fake();
        $dataset = $this->dataset();

        DatasetVersion::where('dataset_id', $dataset->id)->firstOrFail()->touch();

        Queue::assertPushed(SyncDatasetMetadataSummary::class, $this->pushedFor($dataset->id));
    }

    public function test_deleting_a_dataset_still_queues_a_sync(): void
    {
        // Regression: the dataset is soft-deleted before its versions are saved,
        // so a dispatch placed after the observer's Dataset lookup never ran.
        Queue::fake();
        $dataset = $this->dataset();
        $version = DatasetVersion::where('dataset_id', $dataset->id)->firstOrFail();

        $dataset->delete();
        $version->deleted_at = now();
        $version->save();

        Queue::assertPushed(SyncDatasetMetadataSummary::class, $this->pushedFor($dataset->id));
    }
}
