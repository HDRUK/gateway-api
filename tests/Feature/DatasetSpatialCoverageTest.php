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

    public function setUp(): void
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
}
