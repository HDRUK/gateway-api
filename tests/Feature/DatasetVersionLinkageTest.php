<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Publication;
use App\Models\PublicationHasDatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\Gwdm\Gwdm2xHandler;
use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Covers resolved dataset linkage title read-back via DatasetService/Gwdm2xHandler.
 *
 * Drives DatasetService/handlers directly; the x-gwdm-version header steers
 * the target GWDM version via GwdmVersionContext.
 */
class DatasetVersionLinkageTest extends TestCase
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

    private function handler(): Gwdm2xHandler
    {
        return app(GwdmHandlerFactory::class)->resolve('2.0');
    }

    /**
     * Overwrite a version's stored envelope with a specific GWDM shape (as a
     * snapshot row, patch = null) so extractLinkages()'s reconstruction takes
     * the direct extractStoredGwdm() path with no delta replay involved.
     */
    private function overwriteVersionMetadata(int $versionId, array $gwdm): DatasetVersion
    {
        $dv = DatasetVersion::find($versionId);
        $dv->update([
            'metadata' => [
                'gwdmVersion' => '2.0',
                'metadata' => $gwdm,
                'original_metadata' => [],
            ],
            'patch' => null,
        ]);

        return $dv->fresh();
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

    public function test_resolved_linkage_title_tracks_target_latest_version(): void
    {
        $this->disableObservers();

        // Target dataset that the source will link to, and the source itself.
        [$targetDatasetId, $targetVersionId] = $this->createDataset($this->getMetadataV2p0());
        [$sourceDatasetId, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());

        // A resolved linkage: source -> the target's (current latest) version. The junction
        // row freezes dataset_version_target_id, mirroring what extraction stores.
        DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $sourceVersionId,
            'dataset_version_target_id' => $targetVersionId,
            'linkage_type' => 'isDerivedFrom',
            'direct_linkage' => 1,
            'description' => 'Extracted from GWDM',
        ]);

        $originalTitle = Dataset::find($targetDatasetId)->latestMetadata->short_title;

        // Sanity: read-back reflects the target's current title.
        $linkages = $this->handler()->getLinkages($sourceVersionId);
        $this->assertCount(1, $linkages);
        $this->assertSame($originalTitle, $linkages[0]['title']);
        $this->assertStringContainsString('/en/dataset/'.$targetDatasetId, (string) $linkages[0]['url']);

        // The target gains a NEWER version with a changed short_title. The junction row
        // still points at the OLD (frozen) target version id.
        DatasetVersion::create([
            'dataset_id' => $targetDatasetId,
            'version' => 2,
            'metadata' => [],
            'patch' => null,
            'title' => 'Target Title v2',
            'short_title' => 'Target Short Title v2',
            'gwdm_version' => '2.0',
        ]);

        // getLinkages() now reflects the target's LATEST title (not the frozen one)...
        $linkages = $this->handler()->getLinkages($sourceVersionId);
        $this->assertCount(1, $linkages);
        $this->assertSame('Target Short Title v2', $linkages[0]['title']);
        $this->assertStringContainsString('/en/dataset/'.$targetDatasetId, (string) $linkages[0]['url']);

        // ...and so does the afterRead()-driven reconstructed envelope for the source.
        $envelope = $this->service()->getReconstructedMetadataEnvelope($sourceDatasetId, 1, false);
        $datasetLinkage = $envelope['metadata']['linkage']['datasetLinkage'] ?? [];
        $this->assertArrayHasKey('isDerivedFrom', $datasetLinkage);
        $this->assertSame('Target Short Title v2', $datasetLinkage['isDerivedFrom'][0]['title']);
        $this->assertStringContainsString(
            '/en/dataset/'.$targetDatasetId,
            (string) $datasetLinkage['isDerivedFrom'][0]['url'],
        );
    }

    public function test_write_linkages_preserves_existing_rows_when_linkage_key_omitted(): void
    {
        $this->disableObservers();

        [$targetDatasetId] = $this->createDataset($this->getMetadataV2p0());
        [, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());
        $targetPid = Dataset::find($targetDatasetId)->pid;

        // Extraction runs against the source version with a real linkage section.
        $dv = $this->overwriteVersionMetadata($sourceVersionId, [
            'linkage' => [
                'datasetLinkage' => [
                    'isDerivedFrom' => [
                        ['pid' => $targetPid, 'url' => null, 'title' => null],
                    ],
                ],
                'publicationAboutDataset' => [],
                'publicationUsingDataset' => [],
            ],
        ]);
        $this->handler()->extractLinkages($dv);

        $this->assertCount(1, $this->handler()->getLinkages($sourceVersionId));

        // Re-run extraction against the SAME version id (mirrors updateV2()'s in-place
        // reuse, or a manual re-dispatch/repair) but this time the metadata has no
        // `linkage` key at all — e.g. a partial update that never touched linkage.
        $dv = $this->overwriteVersionMetadata($sourceVersionId, [
            // no 'linkage' key
        ]);
        $this->handler()->extractLinkages($dv);

        // The previously-extracted link must survive — omission is not a clear.
        $this->assertCount(1, $this->handler()->getLinkages($sourceVersionId));
    }

    public function test_write_linkages_clears_rows_when_linkage_explicitly_emptied(): void
    {
        $this->disableObservers();

        [$targetDatasetId] = $this->createDataset($this->getMetadataV2p0());
        [, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());
        $targetPid = Dataset::find($targetDatasetId)->pid;

        $dv = $this->overwriteVersionMetadata($sourceVersionId, [
            'linkage' => [
                'datasetLinkage' => [
                    'isDerivedFrom' => [
                        ['pid' => $targetPid, 'url' => null, 'title' => null],
                    ],
                ],
                'publicationAboutDataset' => [],
                'publicationUsingDataset' => [],
            ],
        ]);
        $this->handler()->extractLinkages($dv);

        $this->assertCount(1, $this->handler()->getLinkages($sourceVersionId));

        // Re-run extraction against the SAME version id, this time with the linkage
        // section explicitly present but empty — this must still clear, unlike omission.
        $dv = $this->overwriteVersionMetadata($sourceVersionId, [
            'linkage' => [
                'datasetLinkage' => [],
                'publicationAboutDataset' => [],
                'publicationUsingDataset' => [],
            ],
        ]);
        $this->handler()->extractLinkages($dv);

        $this->assertCount(0, $this->handler()->getLinkages($sourceVersionId));
    }

    /** Both read paths run off the SAME shared resolver — they must agree on the resolved set. */
    public function test_after_read_and_get_linkages_resolve_the_same_dataset_set(): void
    {
        $this->disableObservers();

        [$targetDatasetId, $targetVersionId] = $this->createDataset($this->getMetadataV2p0());
        [, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());

        DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $sourceVersionId,
            'dataset_version_target_id' => $targetVersionId,
            'linkage_type' => 'isDerivedFrom',
            'direct_linkage' => 1,
            'description' => 'Extracted from GWDM',
        ]);

        // Flat `linkages` attribute (getLinkages).
        $flat = $this->handler()->getLinkages($sourceVersionId);
        $this->assertCount(1, $flat);
        $this->assertSame($targetDatasetId, $flat[0]['dataset_id']);
        $this->assertSame('isDerivedFrom', $flat[0]['linkage_type']);

        // GWDM linkage block (afterRead) — same dataset, same title.
        $block = $this->handler()->afterRead(DatasetVersion::find($sourceVersionId))['linkage']['datasetLinkage'] ?? [];
        $this->assertArrayHasKey('isDerivedFrom', $block);
        $this->assertCount(1, $block['isDerivedFrom']);
        $this->assertStringContainsString('/en/dataset/'.$targetDatasetId, (string) $block['isDerivedFrom'][0]['url']);
        $this->assertSame($flat[0]['title'], $block['isDerivedFrom'][0]['title']);
    }

    /** An ARCHIVED (non-ACTIVE) linkage target is dropped by the shared resolver, so both paths hide it. */
    public function test_archived_linkage_target_is_excluded_from_both_paths(): void
    {
        $this->disableObservers();

        [$targetDatasetId, $targetVersionId] = $this->createDataset($this->getMetadataV2p0());
        [, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());

        DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $sourceVersionId,
            'dataset_version_target_id' => $targetVersionId,
            'linkage_type' => 'isDerivedFrom',
            'direct_linkage' => 1,
            'description' => 'Extracted from GWDM',
        ]);

        // ACTIVE target: present in both.
        $this->assertCount(1, $this->handler()->getLinkages($sourceVersionId));

        Dataset::find($targetDatasetId)->update(['status' => Dataset::STATUS_ARCHIVED]);

        // Now excluded: flat attribute empty, and afterRead has no SQL rows to return
        // so it falls through to the stored JSON blob (returns []).
        $this->assertCount(0, $this->handler()->getLinkages($sourceVersionId));
        $this->assertSame([], $this->handler()->afterRead(DatasetVersion::find($sourceVersionId)));
    }

    /** No extracted rows → afterRead returns [] so the envelope falls back to the stored JSON blob. */
    public function test_after_read_returns_empty_when_no_extracted_rows(): void
    {
        $this->disableObservers();

        [, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());

        $this->assertSame([], $this->handler()->afterRead(DatasetVersion::find($sourceVersionId)));
    }

    /**
     * A version with a publication linkage but NO dataset linkage must emit
     * datasetLinkage as null, not []. An empty PHP array encodes to `[]`, which
     * fails GWDM validation ("datasetLinkage must be object or null") — this is the
     * regression behind the failed reconstruction of publication-only datasets.
     */
    public function test_after_read_emits_null_dataset_linkage_when_only_publication_linked(): void
    {
        $this->disableObservers();

        [, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());

        // A publication link (but deliberately no dataset-to-dataset linkage rows).
        $publication = Publication::factory()->create(['paper_doi' => '10.1371/journal.pone.0338652']);
        PublicationHasDatasetVersion::create([
            'publication_id' => $publication->id,
            'dataset_version_id' => $sourceVersionId,
            'link_type' => 'USING',
            'description' => Gwdm2xHandler::LINKAGE_DESCRIPTION,
        ]);

        $linkage = $this->handler()->afterRead(DatasetVersion::find($sourceVersionId))['linkage'];

        // Publication link present, so afterRead does NOT fall through to [].
        $this->assertSame(['10.1371/journal.pone.0338652'], $linkage['publicationUsingDataset']);
        // The key fix: no dataset links → null, never an empty array.
        $this->assertNull($linkage['datasetLinkage']);

        // And the encoded shape is a JSON null (object-or-null), not a `[]` array.
        $this->assertStringContainsString('"datasetLinkage":null', json_encode($linkage));
    }

    /**
     * `paper_doi` is stored inconsistently — bare, `doi.org/...`, or `https://doi.org/...`.
     * The GWDM Doi schema only accepts the bare form, so afterRead() must strip any doi.org
     * host prefix (scheme optional) when rebuilding the publication linkage arrays.
     */
    public function test_after_read_strips_doi_org_prefix_from_publication_dois(): void
    {
        $this->disableObservers();

        [, $sourceVersionId] = $this->createDataset($this->getMetadataV2p0());

        $stored = [
            'https://doi.org/10.1111/tme.12750' => 'ABOUT',
            'doi.org/10.1000/xyz123' => 'ABOUT',
            '10.1371/journal.pone.0338652' => 'USING',
        ];

        foreach ($stored as $paperDoi => $linkType) {
            $publication = Publication::factory()->create(['paper_doi' => $paperDoi]);
            PublicationHasDatasetVersion::create([
                'publication_id' => $publication->id,
                'dataset_version_id' => $sourceVersionId,
                'link_type' => $linkType,
                'description' => Gwdm2xHandler::LINKAGE_DESCRIPTION,
            ]);
        }

        $linkage = $this->handler()->afterRead(DatasetVersion::find($sourceVersionId))['linkage'];

        // Every emitted DOI is the bare form, regardless of how it was stored.
        $this->assertEqualsCanonicalizing(
            ['10.1111/tme.12750', '10.1000/xyz123'],
            $linkage['publicationAboutDataset']
        );
        $this->assertSame(['10.1371/journal.pone.0338652'], $linkage['publicationUsingDataset']);
    }
}
