<?php

namespace Tests\Unit\Services\Search;

use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Search\MetadataSummaryIndex;
use App\Services\TypesenseService;
use Mockery;
use Mockery\MockInterface;
use ReflectionMethod;
use Tests\TestCase;

class MetadataSummaryIndexTest extends TestCase
{
    private MockInterface $typesense;

    private MockInterface $writer;

    private MetadataSummaryIndex $index;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'typesense.summary_embedding_config.import_batch_size' => 2,
            'typesense.summary_embedding_config.import_timeout' => 30,
        ]);

        $this->writer = Mockery::mock(TypesenseService::class);
        $this->typesense = Mockery::mock(TypesenseService::class);
        $this->typesense->shouldReceive('withTimeout')->with(30)->andReturn($this->writer);
        // Writes must go through the timed client, never the default one.
        $this->typesense->shouldNotReceive('importDocuments', 'deleteDocumentsWhere');

        $this->index = new MetadataSummaryIndex($this->typesense);
    }

    private function invokePrivate(string $method, ...$args): mixed
    {
        return (new ReflectionMethod(MetadataSummaryIndex::class, $method))->invoke($this->index, ...$args);
    }

    private function documents(int $count, int $datasetId = 7): array
    {
        return array_map(fn (int $i) => [
            'id' => "{$datasetId}-0-{$i}",
            'dataset_id' => (string) $datasetId,
            'datasetTitle' => 'Study',
            'name' => "summary_field_{$i}",
        ], range(0, $count - 1));
    }

    private function succeeds(): \Closure
    {
        return fn (string $collection, array $batch) => array_fill(0, count($batch), ['success' => true]);
    }

    /** A searchable version with some descriptive metadata. */
    private function version(
        array $summary = ['abstract' => 'Some abstract', 'description' => 'Some description'], 
        ?string $title = 'Study',
        int $datasetId = 7,
        array $otherMetadata = [],
        ): DatasetVersion
        {
            $envelope = [
                'metadata' => ['summary' => $summary] + $otherMetadata
                ];

            $service = Mockery::mock(DatasetService::class);
            $service->shouldReceive('getReconstructedMetadataEnvelope')->andReturn($envelope);
            $this->app->instance(DatasetService::class, $service);

            $version = Mockery::mock(DatasetVersion::class)->makePartial();
            $version->shouldReceive('shouldBeSearchable')->andReturn(true);
            $version->dataset_id = $datasetId;
            $version->version = 1;
            $version->title = $title;
            $version->setRelation('dataset', null);

            return $version;
       }
       

    private function rebuildDataset(DatasetVersion $version, array $alreadyIndexed = []): array
    {
        $events = [];
        $count = $this->invokePrivate('rebuildDataset', $version, function (string $event, array $context) use (&$events) {
            $events[] = [$event, $context];
        }, $alreadyIndexed);

        return [$count, $events];
    }

    public function test_import_sends_batches_and_reports_running_totals(): void
    {
        $sizes = [];
        $this->writer->shouldReceive('importDocuments')
            ->times(3)
            ->with(MetadataSummaryIndex::collectionName(), Mockery::type('array'), ['action' => 'upsert'])
            ->andReturnUsing(function (string $collection, array $batch) use (&$sizes) {
                $sizes[] = count($batch);

                return array_fill(0, count($batch), ['success' => true]);
            });

        $done = [];
        $count = $this->invokePrivate('import', $this->documents(5), function (int $sent) use (&$done) {
            $done[] = $sent;
        });

        $this->assertSame(5, $count);
        $this->assertSame([2, 2, 1], $sizes);
        $this->assertSame([2, 4, 5], $done);
    }

    public function test_import_counts_rejected_variables_out(): void
    {
        $this->writer->shouldReceive('importDocuments')->andReturn(
            [['success' => true], ['success' => false, 'error' => 'bad']],
            [['success' => true]],
        );

        $this->assertSame(2, $this->invokePrivate('import', $this->documents(3)));
    }

    public function test_remove_dataset_deletes_through_the_timed_client(): void
    {
        $this->writer->shouldReceive('deleteDocumentsWhere')
            ->once()
            ->with(MetadataSummaryIndex::collectionName(), 'dataset_id:=7');

        $this->index->removeDataset(7);
    }

    public function test_indexed_counts_reads_every_dataset_from_the_facet(): void
    {
        $this->writer->shouldReceive('rawSearch')
            ->once()
            ->with(MetadataSummaryIndex::collectionName(), '*', Mockery::on(
                fn (array $params) => $params['facet_by'] === 'dataset_id' && $params['max_facet_values'] > 250
            ))
            ->andReturn(['facet_counts' => [['counts' => [
                ['value' => '7', 'count' => 4898],
                ['value' => '12', 'count' => 3],
            ]]]]);

        $this->assertSame([7 => 4898, 12 => 3], $this->invokePrivate('indexedCounts'));
    }

    public function test_rebuild_dataset_imports_and_reports_each_batch(): void
    {
        $this->writer->shouldReceive('importDocuments')->times(2)->andReturnUsing($this->succeeds());

        [$count, $events] = $this->rebuildDataset($this->version());

        $this->assertSame(3, $count);
        $this->assertSame(['dataset', 'batch', 'batch', 'indexed'], array_column($events, 0));
        $this->assertSame(['dataset_id' => 7, 'title' => 'Study', 'documents' => 3], $events[0][1]);
        $this->assertSame(['dataset_id' => 7, 'documents' => 3, 'failed' => 0], $events[3][1]);
    }

    public function test_resume_skips_a_dataset_already_fully_indexed(): void
    {
        $this->writer->shouldNotReceive('importDocuments');

        [$count, $events] = $this->rebuildDataset($this->version(), [7 => 3]);

        $this->assertSame(0, $count);
        $this->assertSame([['skipped', ['dataset_id' => 7, 'reason' => 'already indexed']]], $events);
    }

    public function test_resume_reindexes_a_partly_indexed_dataset_from_scratch(): void
    {
        $this->writer->shouldReceive('deleteDocumentsWhere')->once()->with(MetadataSummaryIndex::collectionName(), 'dataset_id:=7');
        $this->writer->shouldReceive('importDocuments')->times(2)->andReturnUsing($this->succeeds());

        [$count] = $this->rebuildDataset($this->version(), [7 => 2]);

        $this->assertSame(3, $count);
    }

    public function test_a_dataset_without_descriptive_metadata_is_skipped(): void
    {
        $version = $this->version();
        $version->title = '';
        $this->app->instance(DatasetService::class, tap(Mockery::mock(DatasetService::class), fn ($s) => $s
            ->shouldReceive('getReconstructedMetadataEnvelope')->andReturn(['metadata' => []])));

        [$count, $events] = $this->rebuildDataset($version);

        $this->assertSame(0, $count);
        $this->assertSame([['skipped', ['dataset_id' => 7, 'reason' => 'no summary metadata']]], $events);
    }

    public function test_a_failing_dataset_is_reported_rather_than_stopping_the_rebuild(): void
    {
        $this->writer->shouldReceive('importDocuments')->andThrow(new \RuntimeException('Operation timed out'));

        [$count, $events] = $this->rebuildDataset($this->version());

        $this->assertSame(0, $count);
        $this->assertSame(['failed', ['dataset_id' => 7, 'error' => 'Operation timed out']], end($events));
    }

    public function test_every_summary_field_becomes_a_document(): void
    {
        $documents = collect($this->index->documentsFor($this->version(
            summary: [
                'abstract' => 'Some abstract',
                'description' => 'Some description',
                'keywords' => 'adhd;,;autism',
                'datasetAliases' => ['EBC'],
            ],
        )))->keyBy('id');

        $this->assertSame(
            ['7-title',  '7-keywords', '7-aliases', '7-abstract', '7-description'],
            $documents->keys()->all(),
        );
        $this->assertSame('keywords', $documents['7-keywords']['field']);
        $this->assertSame('adhd, autism', $documents['7-keywords']['text']);
        $this->assertSame('EBC', $documents['7-aliases']['text']);
    }

    public function test_empty_fields_are_left_out(): void
    {
        $documents = collect($this->index->documentsFor($this->version(['abstract' => 'An abstract.', 'description' => '   '])))->keyBy('id');
        $this->assertSame(['7-title', '7-abstract'], $documents->keys()->all());
    }

    public function test_keywords_are_trimmed_and_repeats_ignoring_case_are_dropped(): void
    {
        $documents = collect($this->index->documentsFor($this->version(['keywords' => 'Asthma;,; asthma ;,;COPD;,;'])))->keyBy('id');
        $this->assertSame('Asthma, COPD', $documents['7-keywords']['text']);
    }

    public function test_email_addresses_are_removed(): void
    {
        $documents = collect($this->index->documentsFor($this->version([
            'abstract' => 'For access email testing@example.com or the team.',
        ])))->keyBy('id');

        $this->assertSame('For access email [email removed] or the team.', $documents['7-abstract']['text']);
    }

    public function test_html_and_markdown_are_stripped(): void
    {
        $documents = collect($this->index->documentsFor($this->version(['abstract' => '<p>**Asthma** &amp; eczema</p>'])))->keyBy('id');
        $this->assertSame('Asthma & eczema', $documents['7-abstract']['text']);
    }

    public function test_every_document_carries_the_dataset_context(): void
    {
        $documents = collect($this->index->documentsFor($this->version([
            'abstract' => 'An abstract.',
            'publisher' => ['name' => 'Health Data Centre', 'gatewayId' => 'uk.health.example'],
            'datasetType' => 'Health and disease;,;Imaging',
            'populationSize' => 4200,
            'inPipeline' => 'Available',
        ])))->keyBy('id');

        foreach ($documents as $doc) {
            $this->assertSame('7', $doc['dataset_id']);
            $this->assertSame('Study', $doc['datasetTitle']);
            $this->assertSame('Health Data Centre', $doc['publisherName']);
            $this->assertSame('uk.health.example', $doc['publisherGatewayId']);
            $this->assertSame(['Health and disease', 'Imaging'], $doc['datasetType']);
            $this->assertSame(4200, $doc['populationSize']);
            $this->assertSame('Available', $doc['inPipeline']);
            $this->assertSame('HDRUK', $doc['partnerContext']);
        }
    }
}

