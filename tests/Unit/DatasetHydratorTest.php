<?php

namespace Tests\Unit;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Services\Search\DatasetHydrator;
use Config;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Unit tests for DatasetHydrator::hydrate().
 *
 * Covers:
 *  1. A dataset whose latest version is a full snapshot is hydrated correctly.
 *  2. A dataset whose latest version is a delta row (patch != null, metadata = [])
 *     is reconstructed from the nearest snapshot and hydrated correctly.
 *     This is the regression test for the bug where DatasetHydrator silently
 *     dropped all delta-versioned datasets from search results.
 *  3. A dataset that does not exist in the DB is dropped from the hit list.
 */
class DatasetHydratorTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
    }

    // -------------------------------------------------------------------------
    // Snapshot version
    // -------------------------------------------------------------------------

    public function test_hydrates_dataset_with_snapshot_version(): void
    {
        [$dataset, $gwdm] = $this->createDatasetWithSnapshotVersion('Snapshot Title');

        $hit = $this->makeHit((string) $dataset->id);

        $results = (new DatasetHydrator)->hydrate([$hit]);

        $this->assertCount(1, $results, 'Expected one hydrated hit for a snapshot dataset');
        $this->assertEquals('Snapshot Title', $results[0]['metadata']['summary']['title']);
        $this->assertArrayHasKey('team', $results[0]);
        $this->assertArrayHasKey('isCohortDiscovery', $results[0]);
    }

    // -------------------------------------------------------------------------
    // Delta version — regression test for the DatasetHydrator fix
    // -------------------------------------------------------------------------

    /**
     * Before the fix, DatasetHydrator accessed `$latestVersion->metadata['metadata']`
     * on a delta row. Delta rows store `metadata = []`, so this returned null and
     * the dataset was silently dropped with a Log::warning.
     *
     * After the fix, the hydrator detects `patch !== null` and reconstructs the
     * full GWDM object from the nearest snapshot + forward delta walk.
     */
    public function test_hydrates_dataset_whose_latest_version_is_a_delta(): void
    {
        [$dataset] = $this->createDatasetWithDeltaVersion('Original Title', 'Updated via Delta');

        $hit = $this->makeHit((string) $dataset->id);

        $results = (new DatasetHydrator)->hydrate([$hit]);

        $this->assertCount(1, $results, 'DatasetHydrator dropped a delta-versioned dataset — reconstruction is broken');
        $this->assertEquals(
            'Updated via Delta',
            $results[0]['metadata']['summary']['title'],
            'Reconstructed metadata should reflect the delta patch, not the original snapshot title'
        );
    }

    public function test_reconstruction_applies_all_deltas_in_sequence(): void
    {
        [$dataset] = $this->createDatasetWithMultipleDeltas('V1 Title', ['V2 Title', 'V3 Title']);

        $hit = $this->makeHit((string) $dataset->id);

        $results = (new DatasetHydrator)->hydrate([$hit]);

        $this->assertCount(1, $results);
        $this->assertEquals('V3 Title', $results[0]['metadata']['summary']['title']);
    }

    // -------------------------------------------------------------------------
    // Delta-row gatewayId (pid) resolution — regression test for GAT-9246
    // -------------------------------------------------------------------------

    /**
     * When the latest version is a DELTA row, the pid-collection pass must read
     * the RECONSTRUCTED metadata (not the empty `metadata` column), otherwise a
     * pid-format publisher.gatewayId is never collected and stays as the raw pid
     * string instead of being resolved to the integer team id.
     */
    public function test_delta_row_resolves_pid_gateway_id_to_integer_team_id(): void
    {
        $pid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $team = Team::factory()->create(['pid' => $pid]);
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);

        $gwdm = $this->buildMinimalGwdm($team->id, 'Original Title');
        $gwdm['summary']['publisher']['gatewayId'] = $pid;

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'patch' => null,
            'metadata' => [
                'gwdmVersion' => Config::get('metadata.GWDM.version'),
                'metadata' => $gwdm,
                'original_metadata' => [],
            ],
            'title' => 'Original Title',
            'short_title' => 'Original Title',
        ]);

        // v2 delta -> metadata column is [], so the pid only exists in the
        // reconstructed envelope.
        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 2,
            'patch' => [['op' => 'replace', 'path' => '/summary/title', 'value' => 'Updated via Delta']],
            'metadata' => [],
            'title' => 'Updated via Delta',
            'short_title' => 'Updated via Delta',
        ]);

        $results = (new DatasetHydrator)->hydrate([$this->makeHit((string) $dataset->id)]);

        $this->assertCount(1, $results);
        $this->assertSame(
            $team->id,
            $results[0]['metadata']['summary']['publisher']['gatewayId'],
            'delta-latest datasets must resolve the pid gatewayId to the integer team id, not leave the raw pid string'
        );
    }

    /**
     * Guard against the per-hit exists()/refetch N+1 regressing: hydrating a page
     * of several delta-latest datasets must stay within a bounded query budget
     * (previously each hit issued an extra exists() + row refetch + TRASER call).
     */
    public function test_hydrating_a_page_of_delta_rows_stays_within_a_query_budget(): void
    {
        $datasets = [];
        for ($i = 0; $i < 3; $i++) {
            [$dataset] = $this->createDatasetWithDeltaVersion("Original {$i}", "Updated {$i}");
            $datasets[] = $dataset;
        }

        $hits = array_map(fn ($d) => $this->makeHit((string) $d->id), $datasets);

        \DB::flushQueryLog();
        \DB::enableQueryLog();
        $results = (new DatasetHydrator)->hydrate($hits);
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertCount(3, $results);
        // A regression to the old per-hit exists()+refetch path would push this past 25.
        $this->assertLessThanOrEqual(
            25,
            $queryCount,
            "hydrate() issued {$queryCount} queries for 3 delta rows — check for a reintroduced per-hit N+1"
        );
    }

    // -------------------------------------------------------------------------
    // Missing dataset
    // -------------------------------------------------------------------------

    public function test_drops_hit_whose_dataset_id_does_not_exist_in_the_database(): void
    {
        $hit = $this->makeHit('99999');

        $results = (new DatasetHydrator)->hydrate([$hit]);

        $this->assertEmpty($results, 'Hit for a non-existent dataset should be dropped');
    }

    public function test_partial_results_when_some_ids_are_missing(): void
    {
        [$dataset] = $this->createDatasetWithSnapshotVersion('Real Dataset');

        $results = (new DatasetHydrator)->hydrate([
            $this->makeHit((string) $dataset->id),
            $this->makeHit('99999'),
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('Real Dataset', $results[0]['metadata']['summary']['title']);
    }

    // -------------------------------------------------------------------------
    // Soft-deleted team — regression test for the "Attempt to read property
    // id on null" crash on $model->team->id when the owning team was
    // soft-deleted but the dataset itself remains active.
    // -------------------------------------------------------------------------

    public function test_drops_hit_whose_team_has_been_soft_deleted(): void
    {
        [$dataset] = $this->createDatasetWithSnapshotVersion('Orphaned Team Dataset');
        $dataset->team->delete();

        $hit = $this->makeHit((string) $dataset->id);

        $results = (new DatasetHydrator)->hydrate([$hit]);

        $this->assertEmpty($results, 'Hit whose team was soft-deleted should be dropped, not crash');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Elasticsearch hit array for a given dataset ID.
     */
    private function makeHit(string $datasetId): array
    {
        return [
            '_id' => $datasetId,
            '_score' => 1.0,
            '_index' => 'datasets',
            '_source' => [
                'abstract' => '',
                'description' => '',
                'keywords' => '',
                'named_entities' => [],
                'publisherName' => '',
                'shortTitle' => '',
                'title' => '',
                'dataUseTitles' => [],
                'populationSize' => 0,
            ],
            'highlight' => ['abstract' => [], 'description' => []],
        ];
    }

    /**
     * Create a team + dataset + v1 snapshot DatasetVersion.
     *
     * @return array{0: Dataset, 1: array} [$dataset, $gwdm]
     */
    private function createDatasetWithSnapshotVersion(string $title): array
    {
        $team = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $gwdm = $this->buildMinimalGwdm($team->id, $title);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'patch' => null,
            'metadata' => [
                'gwdmVersion' => Config::get('metadata.GWDM.version'),
                'metadata' => $gwdm,
                'original_metadata' => [],
            ],
            'title' => $title,
            'short_title' => $title,
        ]);

        return [$dataset, $gwdm];
    }

    /**
     * Create a team + dataset with a v1 snapshot and a v2 delta changing title.
     *
     * @return array{0: Dataset, 1: Team}
     */
    private function createDatasetWithDeltaVersion(string $originalTitle, string $updatedTitle): array
    {
        $team = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $gwdm = $this->buildMinimalGwdm($team->id, $originalTitle);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'patch' => null,
            'metadata' => [
                'gwdmVersion' => Config::get('metadata.GWDM.version'),
                'metadata' => $gwdm,
                'original_metadata' => [],
            ],
            'title' => $originalTitle,
            'short_title' => $originalTitle,
        ]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 2,
            'patch' => [['op' => 'replace', 'path' => '/summary/title', 'value' => $updatedTitle]],
            'metadata' => [],
            'title' => $updatedTitle,
            'short_title' => $updatedTitle,
        ]);

        return [$dataset, $team];
    }

    /**
     * Create a dataset with one snapshot and N sequential delta versions.
     *
     * @return array{0: Dataset, 1: Team}
     */
    private function createDatasetWithMultipleDeltas(string $v1Title, array $deltaTitles): array
    {
        $team = Team::factory()->create();
        $dataset = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $gwdm = $this->buildMinimalGwdm($team->id, $v1Title);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'patch' => null,
            'metadata' => [
                'gwdmVersion' => Config::get('metadata.GWDM.version'),
                'metadata' => $gwdm,
                'original_metadata' => [],
            ],
            'title' => $v1Title,
            'short_title' => $v1Title,
        ]);

        foreach ($deltaTitles as $i => $title) {
            DatasetVersion::create([
                'dataset_id' => $dataset->id,
                'version' => $i + 2,
                'patch' => [['op' => 'replace', 'path' => '/summary/title', 'value' => $title]],
                'metadata' => [],
                'title' => $title,
                'short_title' => $title,
            ]);
        }

        return [$dataset, $team];
    }

    private function buildMinimalGwdm(int $teamId, string $title): array
    {
        return [
            'summary' => [
                'title' => $title,
                'shortTitle' => $title,
                'abstract' => 'Test abstract.',
                'publisher' => [
                    'gatewayId' => (string) $teamId,
                    'publisherName' => 'Test Publisher',
                ],
            ],
            'coverage' => [],
            'provenance' => [],
            'accessibility' => ['access' => ['accessServiceCategory' => null]],
            'enrichmentAndLinkage' => [],
            'observations' => [],
            'structuralMetadata' => [],
            'required' => [
                'gatewayId' => (string) $teamId,
                'gatewayPid' => '',
                'issued' => now()->toIso8601String(),
                'modified' => now()->toIso8601String(),
                'revisions' => [],
            ],
        ];
    }
}
