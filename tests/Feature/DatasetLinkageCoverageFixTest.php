<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Regression coverage for the GWDM version-handler review fixes:
 *
 *  - H-a: getLinkages() must project raw_url/raw_title so UNRESOLVED (free-text)
 *         linkages round-trip with their title/url instead of rendering null.
 *  - H-b: extractLinkages() must read the reconstructed-from-blob GWDM WITHOUT the
 *         afterRead() SQL overlay, so re-running extraction cannot feed stale
 *         junction-table linkage back into the rows it is about to rewrite.
 *  - M5:  spatial coverage is scoped to the latest version, so editing coverage
 *         (England -> Scotland) reflects only the new value, not a growing union.
 *
 * Drives DatasetService/handlers directly; the x-gwdm-version header steers the
 * target GWDM version via GwdmVersionContext.
 */
class DatasetLinkageCoverageFixTest extends TestCase
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

    // ── H-a ─────────────────────────────────────────────────────────────────

    public function test_unresolved_linkage_roundtrips_title_and_url_via_get_linkages(): void
    {
        $this->disableObservers();

        [, $versionId] = $this->createDataset($this->getMetadataV2p0());

        // An unresolved (free-text) linkage: no target dataset, raw reference only.
        DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $versionId,
            'dataset_version_target_id' => null,
            'linkage_type' => 'isDerivedFrom',
            'direct_linkage' => 1,
            'description' => 'Extracted from GWDM',
            'raw_url' => 'https://example.org/dataset/unknown-ref',
            'raw_pid' => 'free-text-pid',
            'raw_title' => 'Free Text Linked Dataset',
        ]);

        $linkages = $this->service()->getLinkages($versionId);

        $this->assertCount(1, $linkages);
        $this->assertSame('Free Text Linked Dataset', $linkages[0]['title']);
        $this->assertSame('https://example.org/dataset/unknown-ref', $linkages[0]['url']);
        $this->assertNull($linkages[0]['dataset_id']);
        $this->assertSame('isDerivedFrom', $linkages[0]['linkage_type']);
    }

    // ── H-b ─────────────────────────────────────────────────────────────────

    public function test_extract_linkages_uses_blob_not_stale_sql_overlay(): void
    {
        $this->disableObservers();

        // Stored blob linkage points to the "blob" reference.
        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['linkage']['datasetLinkage'] = [
            'isDerivedFrom' => [
                ['url' => 'https://example.org/dataset/99999999', 'title' => 'Blob Linked'],
            ],
        ];
        $metadata['metadata']['linkage']['publicationAboutDataset'] = null;
        $metadata['metadata']['linkage']['publicationUsingDataset'] = null;

        [, $versionId] = $this->createDataset($metadata, Dataset::STATUS_DRAFT);

        // Simulate pre-existing (stale) junction-table linkage that afterRead() would
        // overlay on the read path. If extractLinkages() consumed that overlay it would
        // rewrite STALE and drop the freshly-authored blob linkage.
        DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $versionId,
            'dataset_version_target_id' => null,
            'linkage_type' => 'isDerivedFrom',
            'direct_linkage' => 1,
            'description' => 'Extracted from GWDM',
            'raw_url' => 'https://example.org/dataset/STALE',
            'raw_title' => 'STALE Linked',
        ]);

        $dv = DatasetVersion::find($versionId);
        app(GwdmHandlerFactory::class)->resolve('2.0')->extractLinkages($dv);

        $rows = DatasetVersionHasDatasetVersion::where('dataset_version_source_id', $versionId)
            ->where('direct_linkage', 1)
            ->where('description', 'Extracted from GWDM')
            ->get();

        // The blob reference wins; the stale SQL overlay must not survive.
        $this->assertTrue(
            $rows->contains(fn ($r) => str_contains((string) $r->raw_url, '99999999')),
            'extractLinkages must write the blob-sourced linkage',
        );
        $this->assertFalse(
            $rows->contains(fn ($r) => str_contains((string) $r->raw_url, 'STALE')),
            'extractLinkages must not resurrect stale SQL-overlay linkage',
        );
    }

    // ── M5 ──────────────────────────────────────────────────────────────────

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
