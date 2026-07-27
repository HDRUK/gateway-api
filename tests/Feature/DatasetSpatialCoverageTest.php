<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Covers Dataset::allSpatialCoverages being scoped to the latest version only,
 * so editing coverage (e.g. England -> Scotland) reflects just the new value
 * rather than a growing union across every version ever written.
 *
 * Drives DatasetService directly; the x-gwdm-version header steers the target
 * GWDM version via GwdmVersionContext.
 */
class DatasetSpatialCoverageTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();
    }

    private function setGwdmHeader(string $version): void
    {
        request()->headers->set('x-gwdm-version', $version);
    }

    private function service(): DatasetService
    {
        return app(DatasetService::class);
    }

    /** Create a 2.0 dataset and return [datasetId, versionId]. */
    private function createDataset(array $metadata, string $status = Dataset::STATUS_ACTIVE): array
    {
        $team = Team::first();
        $user = User::first();

        $this->setGwdmHeader('2.0');
        $created = $this->service()->create(
            [
                'metadata' => $metadata,
                'status' => $status,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            null,
            null,
            false,
        );

        $this->assertTrue($created['translated']);

        return [$created['dataset_id'], $created['version_id']];
    }

    public function test_spatial_coverage_is_scoped_to_latest_version(): void
    {
        $this->disableObservers();

        $team = Team::first();
        $user = User::first();

        // v1 coverage: England.
        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['coverage']['spatial'] = 'England';
        [$datasetId] = $this->createDataset($metadata);

        $regionsV1 = collect(Dataset::find($datasetId)->allSpatialCoverages)->pluck('region')->all();
        $this->assertContains('England', $regionsV1);
        $this->assertNotContains('Scotland', $regionsV1);

        // v2 coverage: Scotland (replaces, does not accumulate).
        $updateMetadata = $this->getMetadataV2p0();
        $updateMetadata['metadata']['coverage']['spatial'] = 'Scotland';

        $this->setGwdmHeader('2.0');
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $updateMetadata, 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            $team->id,
            Dataset::ORIGIN_MANUAL,
            false,
            $team,
        );

        $regionsV2 = collect(Dataset::find($datasetId)->allSpatialCoverages)->pluck('region')->all();
        $this->assertContains('Scotland', $regionsV2);
        $this->assertNotContains(
            'England',
            $regionsV2,
            'allSpatialCoverages must reflect only the latest version, not a union across versions',
        );
    }

    private function updateCoverage(int $datasetId, string $spatial): void
    {
        $team = Team::first();
        $user = User::first();

        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['coverage']['spatial'] = $spatial;

        $this->setGwdmHeader('2.0');
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $metadata, 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            $team->id,
            Dataset::ORIGIN_MANUAL,
            false,
            $team,
        );
    }

    /** mapCoverage() path (b): "united kingdom" expands to every UK region. */
    public function test_united_kingdom_maps_to_all_uk_regions(): void
    {
        $this->disableObservers();

        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['coverage']['spatial'] = 'United Kingdom';
        [$datasetId] = $this->createDataset($metadata);

        $regions = collect(Dataset::find($datasetId)->allSpatialCoverages)->pluck('region')->all();

        foreach (['England', 'Northern Ireland', 'Scotland', 'Wales'] as $ukRegion) {
            $this->assertContains($ukRegion, $regions, "'united kingdom' must map to {$ukRegion}");
        }
        $this->assertNotContains('Rest of the world', $regions);
    }

    /** mapCoverage() path (c): unrecognised coverage falls back to "Rest of the world". */
    public function test_unrecognised_coverage_falls_back_to_rest_of_the_world(): void
    {
        $this->disableObservers();

        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['coverage']['spatial'] = 'Atlantis';
        [$datasetId] = $this->createDataset($metadata);

        $regions = collect(Dataset::find($datasetId)->allSpatialCoverages)->pluck('region')->all();

        $this->assertSame(['Rest of the world'], $regions);
    }

    /**
     * Transition from the UK catch-all (4 regions) down to a single region: the
     * latest-version coverage must be exactly the new region, with the extra UK
     * regions and any world fallback gone (prune-back behaviour).
     */
    public function test_narrowing_from_catch_all_to_single_region_prunes_extras(): void
    {
        $this->disableObservers();

        // v1: United Kingdom -> all four UK regions.
        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['coverage']['spatial'] = 'United Kingdom';
        [$datasetId] = $this->createDataset($metadata);

        $this->assertGreaterThan(
            1,
            count(collect(Dataset::find($datasetId)->allSpatialCoverages)->pluck('region')->all()),
        );

        // v2: England only.
        $this->updateCoverage($datasetId, 'England');

        $regions = collect(Dataset::find($datasetId)->allSpatialCoverages)->pluck('region')->all();
        $this->assertSame(
            ['England'],
            $regions,
            'narrowing to a single region must leave only that region on the latest version',
        );
    }
}
