<?php

namespace Tests\Unit\Services\Search;

use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Search\VariableIndex;
use App\Services\TypesenseService;
use Mockery;
use Mockery\MockInterface;
use ReflectionMethod;
use Tests\TestCase;

class VariableIndexTest extends TestCase
{
    private MockInterface $typesense;

    private MockInterface $writer;

    private VariableIndex $index;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'typesense.variables.import_batch_size' => 2,
            'typesense.variables.import_timeout' => 30,
        ]);

        $this->writer = Mockery::mock(TypesenseService::class);
        $this->typesense = Mockery::mock(TypesenseService::class);
        $this->typesense->shouldReceive('withTimeout')->with(30)->andReturn($this->writer);
        // Writes must go through the timed client, never the default one.
        $this->typesense->shouldNotReceive('importDocuments', 'deleteDocumentsWhere');

        $this->index = new VariableIndex($this->typesense);
    }

    private function invokePrivate(string $method, ...$args): mixed
    {
        return (new ReflectionMethod(VariableIndex::class, $method))->invoke($this->index, ...$args);
    }

    private function documents(int $count, int $datasetId = 7): array
    {
        return array_map(fn (int $i) => [
            'id' => "{$datasetId}-0-{$i}",
            'dataset_id' => (string) $datasetId,
            'datasetTitle' => 'Study',
            'name' => "column_{$i}",
        ], range(0, $count - 1));
    }

    private function succeeds(): \Closure
    {
        return fn (string $collection, array $batch) => array_fill(0, count($batch), ['success' => true]);
    }

    /** A searchable version whose metadata has $columns columns. */
    private function version(int $columns, int $datasetId = 7): DatasetVersion
    {
        $envelope = ['metadata' => ['structuralMetadata' => [[
            'name' => 'Table',
            'columns' => array_map(fn (int $i) => ['name' => "column_{$i}"], range(0, $columns - 1)),
        ]]]];

        $service = Mockery::mock(DatasetService::class);
        $service->shouldReceive('getReconstructedMetadataEnvelope')->andReturn($envelope);
        $this->app->instance(DatasetService::class, $service);

        $version = Mockery::mock(DatasetVersion::class)->makePartial();
        $version->shouldReceive('shouldBeSearchable')->andReturn(true);
        $version->dataset_id = $datasetId;
        $version->version = 1;
        $version->title = 'Study';
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
            ->with(VariableIndex::collectionName(), Mockery::type('array'), ['action' => 'upsert'])
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
            ->with(VariableIndex::collectionName(), 'dataset_id:=7');

        $this->index->removeDataset(7);
    }

    public function test_indexed_counts_reads_every_dataset_from_the_facet(): void
    {
        $this->writer->shouldReceive('rawSearch')
            ->once()
            ->with(VariableIndex::collectionName(), '*', Mockery::on(
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

        [$count, $events] = $this->rebuildDataset($this->version(3));

        $this->assertSame(3, $count);
        $this->assertSame(['dataset', 'batch', 'batch', 'indexed'], array_column($events, 0));
        $this->assertSame(['dataset_id' => 7, 'title' => 'Study', 'variables' => 3], $events[0][1]);
        $this->assertSame(['dataset_id' => 7, 'variables' => 3, 'failed' => 0], $events[3][1]);
    }

    public function test_resume_skips_a_dataset_already_fully_indexed(): void
    {
        $this->writer->shouldNotReceive('importDocuments');

        [$count, $events] = $this->rebuildDataset($this->version(3), [7 => 3]);

        $this->assertSame(0, $count);
        $this->assertSame([['skipped', ['dataset_id' => 7, 'reason' => 'already indexed']]], $events);
    }

    public function test_resume_reindexes_a_partly_indexed_dataset_from_scratch(): void
    {
        $this->writer->shouldReceive('deleteDocumentsWhere')->once()->with(VariableIndex::collectionName(), 'dataset_id:=7');
        $this->writer->shouldReceive('importDocuments')->times(2)->andReturnUsing($this->succeeds());

        [$count] = $this->rebuildDataset($this->version(3), [7 => 2]);

        $this->assertSame(3, $count);
    }

    public function test_a_dataset_without_variables_is_skipped(): void
    {
        $version = $this->version(1);
        $this->app->instance(DatasetService::class, tap(Mockery::mock(DatasetService::class), fn ($s) => $s
            ->shouldReceive('getReconstructedMetadataEnvelope')->andReturn(['metadata' => []])));

        [$count, $events] = $this->rebuildDataset($version);

        $this->assertSame(0, $count);
        $this->assertSame([['skipped', ['dataset_id' => 7, 'reason' => 'no variables']]], $events);
    }

    public function test_a_failing_dataset_is_reported_rather_than_stopping_the_rebuild(): void
    {
        $this->writer->shouldReceive('importDocuments')->andThrow(new \RuntimeException('Operation timed out'));

        [$count, $events] = $this->rebuildDataset($this->version(3));

        $this->assertSame(0, $count);
        $this->assertSame(['failed', ['dataset_id' => 7, 'error' => 'Operation timed out']], end($events));
    }
}
