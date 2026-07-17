<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
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
        $linkages = $this->service()->getLinkages($sourceVersionId);
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
        $linkages = $this->service()->getLinkages($sourceVersionId);
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
}
