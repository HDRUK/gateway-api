<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Services\Search\VariableIndex;
use App\Services\TypesenseService;
use Database\Seeders\PrukCrocodSampleSeeder;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Only the partners in typesense.variables.partners have their variables
 * indexed, by a rebuild and by a single dataset's sync alike.
 */
class VariableIndexPartnersTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    // A PRUK sample dataset with 23 variables, moved to HDRUK by the tests
    // that need one.
    private const PID = '4733b419-6cb8-5178-9ebc-95ae4e4e6858';

    private MockInterface $writer;

    private array $imported = [];

    protected function setUp(): void
    {
        $this->commonSetUp();
        $this->seed(PrukCrocodSampleSeeder::class);

        config(['typesense.variables.partners' => 'PRUK']);

        $this->writer = Mockery::mock(TypesenseService::class);
        $this->writer->shouldReceive('importDocuments')->andReturnUsing(function (string $collection, array $batch) {
            array_push($this->imported, ...$batch);

            return array_fill(0, count($batch), ['success' => true]);
        });
    }

    private function index(bool $collectionExists = false): VariableIndex
    {
        $typesense = Mockery::mock(TypesenseService::class);
        $typesense->shouldReceive('withTimeout')->andReturn($this->writer);
        $typesense->shouldReceive('collectionExists')->andReturn($collectionExists);
        $typesense->shouldReceive('createCollection');

        return new VariableIndex($typesense);
    }

    private function moveToHdruk(): Dataset
    {
        $dataset = Dataset::where('pid', self::PID)->firstOrFail();
        $dataset->update(['partner_context' => 'HDRUK']);

        return $dataset;
    }

    public function test_partners_reads_a_comma_separated_list(): void
    {
        config(['typesense.variables.partners' => ' pruk, CRUK ,,']);

        $this->assertSame(['PRUK', 'CRUK'], VariableIndex::partners());
        $this->assertTrue(VariableIndex::indexesPartner('cruk'));
        $this->assertFalse(VariableIndex::indexesPartner('HDRUK'));
    }

    public function test_no_partners_listed_indexes_every_partner(): void
    {
        config(['typesense.variables.partners' => '']);

        $this->assertSame([], VariableIndex::partners());
        $this->assertTrue(VariableIndex::indexesPartner('HDRUK'));
    }

    public function test_rebuild_only_indexes_the_listed_partners(): void
    {
        $hdruk = $this->moveToHdruk();
        $started = null;

        $this->index()->rebuild(function (string $event, array $context) use (&$started) {
            if ($event === 'started') {
                $started = $context['datasets'];
            }
        });

        $prukDatasets = Dataset::where('partner_context', 'PRUK')->count();
        $this->assertSame($prukDatasets, $started);
        $this->assertNotEmpty($this->imported);
        $this->assertSame(['PRUK'], array_values(array_unique(array_column($this->imported, 'partnerContext'))));
        $this->assertNotContains((string) $hdruk->id, array_column($this->imported, 'dataset_id'));
    }

    public function test_resume_removes_variables_of_partners_no_longer_listed(): void
    {
        $this->writer->shouldReceive('deleteDocumentsWhere')
            ->once()
            ->with(VariableIndex::collectionName(), 'partnerContext:!=[`PRUK`]')
            ->andReturn(['num_deleted' => 4898]);
        $this->writer->shouldReceive('rawSearch')->andReturn(['facet_counts' => []]);
        $pruned = null;

        $this->index(collectionExists: true)->rebuild(function (string $event, array $context) use (&$pruned) {
            if ($event === 'pruned') {
                $pruned = $context['variables'];
            }
        }, resume: true);

        $this->assertSame(4898, $pruned);
    }

    public function test_sync_removes_and_skips_a_dataset_of_an_unlisted_partner(): void
    {
        $hdruk = $this->moveToHdruk();
        $this->writer->shouldReceive('deleteDocumentsWhere')->once()->with(VariableIndex::collectionName(), 'dataset_id:='.$hdruk->id);

        $this->assertSame(0, $this->index(collectionExists: true)->syncDataset($hdruk->id));
        $this->assertSame([], $this->imported);
    }

    public function test_sync_indexes_a_dataset_of_a_listed_partner(): void
    {
        $pruk = Dataset::where('pid', self::PID)->firstOrFail();
        $this->writer->shouldReceive('deleteDocumentsWhere')->once();

        $this->assertSame(23, $this->index(collectionExists: true)->syncDataset($pruk->id));
        $this->assertSame(['PRUK'], array_values(array_unique(array_column($this->imported, 'partnerContext'))));
    }
}
