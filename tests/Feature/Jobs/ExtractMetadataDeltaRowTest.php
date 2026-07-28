<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ExtractPublicationsFromMetadata;
use App\Jobs\ExtractToolsFromMetadata;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Publication;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Services\DatasetService;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Regression coverage for GAT-9018: ExtractToolsFromMetadata::tool() and
 * ExtractPublicationsFromMetadata::publication() used to read
 * dataset_versions.metadata directly via a raw DB::table()+JSON_TYPE() check,
 * which silently no-oped on delta rows — persistMetadataVersion() stores
 * `metadata = []` there (SQL type ARRAY), matching neither the OBJECT nor
 * STRING branch the jobs checked for. They now reconstruct via
 * DatasetService::getReconstructedMetadataEnvelope(), which is correct for
 * both snapshot and delta rows.
 */
class ExtractMetadataDeltaRowTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
        $this->disableObservers();
    }

    private function setGwdmHeader(string $version): void
    {
        request()->headers->set('x-gwdm-version', $version);
    }

    private function service(): DatasetService
    {
        return app(DatasetService::class);
    }

    /**
     * @return array{0: int, 1: DatasetVersion} dataset id and the resulting delta-row version
     */
    private function createDatasetWithDeltaVersion(Team $team, User $user, array $metadata): array
    {
        $this->setGwdmHeader('2.0');

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

        // v2: identical schema -> delta row (metadata column stores `[]`, not the
        // full envelope) — this is the shape that used to break both jobs.
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
        $this->assertSame([], $v2->metadata, 'delta row metadata column must be empty, confirming a raw-column read would find nothing');

        return [$datasetId, $v2];
    }

    public function test_tool_extraction_reads_delta_row_metadata_correctly(): void
    {
        $team = Team::first();
        $user = User::first();
        $tool = Tool::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'enabled' => 1,
        ]);

        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['linkage']['tools'] = config('gateway.gateway_url') . '/tool/' . $tool->id;

        [, $v2] = $this->createDatasetWithDeltaVersion($team, $user, $metadata);

        (new ExtractToolsFromMetadata($v2->id))->tool($v2->id);

        $this->assertDatabaseHas('dataset_version_has_tool', [
            'dataset_version_id' => $v2->id,
            'tool_id' => $tool->id,
            'link_type' => 'Used on',
        ]);
    }

    public function test_publication_extraction_reads_delta_row_metadata_correctly(): void
    {
        $team = Team::first();
        $user = User::first();
        $publication = Publication::factory()->create([
            'team_id' => $team->id,
            'owner_id' => $user->id,
        ]);

        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['linkage']['publicationAboutDataset'] = [
            config('gateway.gateway_url') . '/publication/' . $publication->id,
        ];

        [, $v2] = $this->createDatasetWithDeltaVersion($team, $user, $metadata);

        (new ExtractPublicationsFromMetadata($v2->id))->publication($v2->id);

        $this->assertDatabaseHas('publication_has_dataset_version', [
            'dataset_version_id' => $v2->id,
            'publication_id' => $publication->id,
            'link_type' => 'ABOUT',
        ]);
    }
}
