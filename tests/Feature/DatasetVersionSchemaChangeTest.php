<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Enums\Partner;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * persistMetadataVersion() must force a full snapshot when the GWDM schema
 * version changes between the previous version and the new one, so every
 * delta window stays schema-homogeneous — a patch diffed across differing
 * schemas would be meaningless, and reconstruction resolves its handler from
 * the nearest snapshot's gwdm_version.
 */
class DatasetVersionSchemaChangeTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function service(): DatasetService
    {
        return app(DatasetService::class);
    }

    public function test_a_replaced_working_version_follows_the_schema_of_its_latest_content(): void
    {
        $this->disableObservers();

        $team = Team::first();
        $user = User::first();

        $created = $this->service()->create(
            [
                'metadata' => $this->getMetadataV2p0(),
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            null,
            null,
            targetGwdmVersion: '2.0',
        );
        $datasetId = $created['dataset_id'];

        $saveDraft = function (string $gwdmVersion, array $metadata) use ($datasetId, $user): DatasetVersion {
            $this->service()->update(
                Dataset::findOrFail($datasetId),
                ['metadata' => $metadata, 'status' => Dataset::STATUS_DRAFT],
                $user->id,
                Dataset::ORIGIN_MANUAL,
                Partner::HDRUK,
                targetGwdmVersion: $gwdmVersion,
            );

            return DatasetVersion::where('dataset_id', $datasetId)->where('version', 2)->firstOrFail();
        };

        $working = $saveDraft('2.0', $this->getMetadataV2p0());
        $this->assertNotNull($working->patch, 'same schema as v1 is a diff');

        $working = $saveDraft('2.1', $this->getMetadataV2p1());
        $this->assertNull($working->patch, 'a schema change from v1 makes the working version a full copy');
        $this->assertSame('2.1', $working->gwdm_version);

        $working = $saveDraft('2.0', $this->getMetadataV2p0());
        $this->assertNotNull($working->patch, 'back on v1\'s schema it is a diff again');
        $this->assertSame('2.0', $working->gwdm_version);

        $this->assertSame(2, DatasetVersion::where('dataset_id', $datasetId)->count());
    }

    public function test_schema_change_forces_snapshot_while_same_schema_stays_delta(): void
    {
        // This test asserts the service's snapshot/delta versioning decision.
        // Disable observers so no jobs are dispatched from the create/update model writes.
        $this->disableObservers();

        $team = Team::first();
        $user = User::first();
        $metadata = $this->getMetadataV2p0();

        // --- v1 @ GWDM 2.0 : base row is always a snapshot ---
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
            targetGwdmVersion: '2.0',
        );

        $this->assertTrue($created['translated']);
        $datasetId = $created['dataset_id'];

        $v1 = DatasetVersion::where('dataset_id', $datasetId)->where('version', 1)->first();
        $this->assertNull($v1->patch, 'v1 base row must be a snapshot');
        $this->assertSame('2.0', $v1->gwdm_version);

        // --- v2 @ GWDM 2.0 : schema unchanged -> delta row ---
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $metadata, 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            Dataset::ORIGIN_MANUAL,
            Partner::HDRUK,
            targetGwdmVersion: '2.0',
        );

        $v2 = DatasetVersion::where('dataset_id', $datasetId)->where('version', 2)->first();
        $this->assertNotNull($v2->patch, 'same-schema update should be a delta (patch present)');
        $this->assertSame('2.0', $v2->gwdm_version);

        // --- v3 @ GWDM 2.1 : schema change -> forced snapshot ---
        $this->service()->update(
            Dataset::find($datasetId),
            ['metadata' => $this->getMetadataV2p1(), 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            Dataset::ORIGIN_MANUAL,
            Partner::HDRUK,
            targetGwdmVersion: '2.1',
        );

        $v3 = DatasetVersion::where('dataset_id', $datasetId)->where('version', 3)->first();
        $this->assertNull($v3->patch, 'a schema change must force a full snapshot (patch null)');
        $this->assertSame('2.1', $v3->gwdm_version);
    }
}
