<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Services\Search\DatasetHydrator;
use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Search results carry the provenance of the context that owns the dataset, so
 * a cross-context result can say where it came from ("via Cancer Research UK").
 *
 * partner_name is present only for a context that is not this deployment's own:
 * our own datasets need no provenance line, and the client renders the line
 * when the name is there rather than having to know which context is ours.
 */
class DatasetHydratorPartnerTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_it_names_the_partner_a_dataset_came_from(): void
    {
        Config::set('partners.names.CRUK', 'Cancer Research UK');

        $hit = $this->hydrateOne($this->makeDataset('CRUK'));

        $this->assertSame('CRUK', $hit['partner_context']);
        $this->assertSame('Cancer Research UK', $hit['partner_name']);
    }

    public function test_it_leaves_our_own_datasets_unnamed(): void
    {
        $hit = $this->hydrateOne($this->makeDataset('HDRUK'));

        $this->assertSame('HDRUK', $hit['partner_context']);
        $this->assertNull($hit['partner_name']);
    }

    public function test_the_deployment_s_own_context_is_what_counts_not_hdruk(): void
    {
        // A CRUK deployment's own datasets are the unmarked ones; HDRUK's are
        // the foreign ones.
        Config::set('partners.default', 'CRUK');
        Config::set('partners.names.HDRUK', 'Health Data Research UK');

        $this->assertNull($this->hydrateOne($this->makeDataset('CRUK'))['partner_name']);
        $this->assertSame(
            'Health Data Research UK',
            $this->hydrateOne($this->makeDataset('HDRUK'))['partner_name'],
        );
    }

    public function test_an_unnamed_partner_is_reported_without_a_name(): void
    {
        Config::set('partners.names', []);

        $hit = $this->hydrateOne($this->makeDataset('CRUK'));

        $this->assertSame('CRUK', $hit['partner_context']);
        $this->assertNull($hit['partner_name']);
    }

    private function makeDataset(string $partnerContext): int
    {
        $dataset = Dataset::factory()->create([
            'team_id'         => Team::factory()->create()->id,
            'user_id'         => 1,
            'status'          => Dataset::STATUS_ACTIVE,
            'partner_context' => $partnerContext,
        ]);

        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version'    => 1,
            'metadata'   => ['metadata' => $this->getMetadata()['metadata'], 'gwdmVersion' => '2.2'],
        ]);

        return $dataset->id;
    }

    private function hydrateOne(int $datasetId): array
    {
        $hits = (new DatasetHydrator())->hydrate([
            ['_id' => (string) $datasetId, '_source' => [], 'highlight' => []],
        ]);

        $this->assertCount(1, $hits, 'the dataset was dropped during hydration');

        return $hits[0];
    }
}
