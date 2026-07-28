<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Regression coverage for GAT-9246: DatasetVersion::toSearchableArray() (the
 * Typesense indexing entry point) must build its metadata-derived fields from the
 * RECONSTRUCTED GWDM envelope, not from the raw `metadata` column. Delta rows
 * store `metadata = []`, so a raw-column read would index an empty document for
 * any dataset whose latest version is a delta (versions 2-9, 11-19, ...).
 *
 * This test builds a genuine delta row and asserts the searchable array is fully
 * populated — it fails if toSearchableArray() is reverted to reading $this->metadata.
 */
class DatasetVersionSearchableTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();
        $this->disableObservers();
    }

    private function service(): DatasetService
    {
        return app(DatasetService::class);
    }

    /**
     * Create a dataset and a v2 that is a delta row (metadata column = []).
     *
     * @return DatasetVersion the delta-row version
     */
    private function createDeltaVersion(Team $team, User $user, array $metadata): DatasetVersion
    {
        request()->headers->set('x-gwdm-version', '2.0');

        $created = $this->service()->create(
            [
                'metadata' => $metadata,
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            null,
            null,
            false,
        );
        $datasetId = $created['dataset_id'];

        // v2 with identical schema -> delta row (patch set, metadata column = []).
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $metadata, 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            $team->id,
            Dataset::ORIGIN_MANUAL,
            false,
            $team,
        );

        $v2 = DatasetVersion::where('dataset_id', $datasetId)->where('version', 2)->first();
        $this->assertNotNull($v2, 'v2 should have been created');
        $this->assertNotNull($v2->patch, 'v2 must be a delta row for this regression test to be meaningful');
        $this->assertSame([], $v2->metadata, 'delta row metadata column must be empty — a raw read would index nothing');

        return $v2;
    }

    public function test_to_searchable_array_is_fully_populated_for_a_delta_row(): void
    {
        $team = Team::first();
        $user = User::first();

        $v2 = $this->createDeltaVersion($team, $user, $this->getMetadataV2p0());

        $searchable = DatasetVersion::find($v2->id)->toSearchableArray();

        // Identity/DB-column fields.
        $this->assertSame((string) $v2->id, $searchable['id']);
        $this->assertSame((string) $v2->dataset_id, $searchable['dataset_id']);
        $this->assertSame(
            'Publications that mention HDR-UK (or any variant thereof) in Acknowledgements or Author Affiliations',
            $searchable['title']
        );
        $this->assertSame('HDR UK Papers & Preprints', $searchable['shortTitle']);

        // Metadata-derived fields — these are ONLY correct if the envelope was
        // reconstructed (the delta row's metadata column is []).
        $this->assertNotEmpty($searchable['abstract'], 'abstract must come from the reconstructed envelope');
        $this->assertStringStartsWith('Publications that mention HDR-UK', $searchable['abstract']);
        $this->assertContains('list of papers', $searchable['dataType']);

        // Material types (version-specific getMaterialTypes) — fixture has Blood + Urine.
        $this->assertTrue($searchable['containsBioSamples']);
        $this->assertContains('Blood', $searchable['sampleAvailability']);
        $this->assertContains('Urine', $searchable['sampleAvailability']);
        // Must serialise as a JSON array, never an object (array_values fix).
        $this->assertTrue(
            array_is_list($searchable['sampleAvailability']),
            'sampleAvailability must be a sequential list so it serialises as a JSON array'
        );

        // Relationship-derived fields.
        $this->assertIsArray($searchable['geographicLocation']);
        $this->assertIsBool($searchable['isCohortDiscovery']);
    }

    public function test_to_searchable_array_matches_between_snapshot_and_delta(): void
    {
        $team = Team::first();
        $user = User::first();

        $metadata = $this->getMetadataV2p0();
        $v2 = $this->createDeltaVersion($team, $user, $metadata);

        $v1 = DatasetVersion::where('dataset_id', $v2->dataset_id)->where('version', 1)->first();
        $this->assertNull($v1->patch, 'v1 must be a snapshot');

        $snapshotSearchable = DatasetVersion::find($v1->id)->toSearchableArray();
        $deltaSearchable = DatasetVersion::find($v2->id)->toSearchableArray();

        // Same underlying metadata -> identical metadata-derived fields regardless
        // of whether the row is a snapshot or a reconstructed delta.
        foreach (['abstract', 'description', 'keywords', 'dataType', 'publisherName', 'sampleAvailability', 'containsBioSamples'] as $key) {
            $this->assertEquals(
                $snapshotSearchable[$key],
                $deltaSearchable[$key],
                "searchable field '{$key}' should match between snapshot and delta rows"
            );
        }
    }
}
