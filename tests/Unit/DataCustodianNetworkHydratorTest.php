<?php

namespace Tests\Unit;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\DataProviderColl;
use App\Services\Search\DataCustodianNetworkHydrator;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Unit tests for DataCustodianNetworkHydrator::hydrate().
 *
 * Covers the OOM fix: dataset titles used to come from an eager-loaded
 * `latestMetadata` relation with no column restriction, loading the full
 * GWDM metadata JSON (avg ~120KB, up to several MB) for every active
 * dataset across a network's member teams. The fix selects only
 * `short_title` up front, falling back to parsing full metadata only for
 * the rows that actually lack it.
 */
class DataCustodianNetworkHydratorTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    public function test_hydrates_network_using_short_title_when_present(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => [
                'gwdmVersion' => Config::get('metadata.GWDM.version'),
                'metadata'    => ['summary' => ['shortTitle' => 'Should not be used']],
            ],
            'title'       => 'Full Title',
            'short_title' => 'Cheap Short Title',
        ]);

        $network = DataProviderColl::factory()->create(['enabled' => true]);
        $network->teams()->attach($team->id);

        $results = (new DataCustodianNetworkHydrator())->hydrate([$this->makeHit($network->id)]);

        $this->assertCount(1, $results);
        $this->assertEquals(['Cheap Short Title'], $results[0]['datasetTitles']);
    }

    public function test_falls_back_to_metadata_json_when_short_title_is_empty(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => [
                'gwdmVersion' => Config::get('metadata.GWDM.version'),
                'metadata'    => ['summary' => ['shortTitle' => 'Fallback Title From Metadata']],
            ],
            'title'       => 'Full Title',
            'short_title' => '',
        ]);

        $network = DataProviderColl::factory()->create(['enabled' => true]);
        $network->teams()->attach($team->id);

        $results = (new DataCustodianNetworkHydrator())->hydrate([$this->makeHit($network->id)]);

        $this->assertCount(1, $results);
        $this->assertEquals(['Fallback Title From Metadata'], $results[0]['datasetTitles']);
    }

    public function test_skips_dataset_with_no_title_available_at_all(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => [
                'gwdmVersion' => Config::get('metadata.GWDM.version'),
                'metadata'    => ['summary' => []],
            ],
            'title'       => '',
            'short_title' => '',
        ]);

        $network = DataProviderColl::factory()->create(['enabled' => true]);
        $network->teams()->attach($team->id);

        $results = (new DataCustodianNetworkHydrator())->hydrate([$this->makeHit($network->id)]);

        $this->assertCount(1, $results);
        $this->assertEquals([], $results[0]['datasetTitles']);
    }

    public function test_ignores_inactive_datasets(): void
    {
        $team = Team::factory()->create(['enabled' => true]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);

        DatasetVersion::create([
            'dataset_id'  => $dataset->id,
            'version'     => 1,
            'patch'       => null,
            'metadata'    => ['gwdmVersion' => Config::get('metadata.GWDM.version'), 'metadata' => []],
            'title'       => 'Archived Dataset',
            'short_title' => 'Archived Dataset',
        ]);

        $network = DataProviderColl::factory()->create(['enabled' => true]);
        $network->teams()->attach($team->id);

        $results = (new DataCustodianNetworkHydrator())->hydrate([$this->makeHit($network->id)]);

        $this->assertCount(1, $results);
        $this->assertEquals([], $results[0]['datasetTitles']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeHit(int $networkId): array
    {
        return [
            '_id'     => (string) $networkId,
            '_score'  => 1.0,
            '_source' => [],
        ];
    }
}
