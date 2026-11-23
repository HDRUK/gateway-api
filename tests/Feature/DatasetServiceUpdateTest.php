<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Enums\Partner;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class DatasetServiceUpdateTest extends TestCase
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

    public function test_a_save_refused_for_a_concurrent_publish_leaves_the_dataset_unchanged(): void
    {
        $team = Team::first();
        $user = User::first();
        $otherUser = User::where('id', '!=', $user->id)->firstOrFail();
        $created = $this->service()->create(
            [
                'metadata' => $this->getMetadataV2p0(),
                'status' => Dataset::STATUS_DRAFT,
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
        $fields = ['status', 'user_id', 'partner_context'];
        $before = Dataset::findOrFail($datasetId)->only($fields);

        $this->enableObservers();
        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Dataset::saved(function () use ($datasetId) {
            DB::table('dataset_versions')
                ->where('dataset_id', $datasetId)
                ->where('version', 1)
                ->update(['active_date' => '2026-11-20 09:00:00']);
        });

        try {
            $this->service()->update(
                Dataset::findOrFail($datasetId),
                ['metadata' => $this->getMetadataV2p0(), 'status' => Dataset::STATUS_ACTIVE],
                $otherUser->id,
                Dataset::ORIGIN_MANUAL,
                Partner::CRUK,
                targetGwdmVersion: '2.0',
            );
            $this->fail('Expected the update to refuse to replace a version published mid-save.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('was published while this save was in progress', $e->getMessage());
        }

        $this->assertSame($before, Dataset::findOrFail($datasetId)->only($fields));
    }

    public function test_a_working_version_published_by_another_save_mid_update_is_not_overwritten(): void
    {
        $team = Team::first();
        $user = User::first();
        $created = $this->service()->create(
            [
                'metadata' => $this->getMetadataV2p0(),
                'status' => Dataset::STATUS_DRAFT,
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
        $before = DatasetVersion::where('dataset_id', $datasetId)->where('version', 1)->firstOrFail()->getAttributes();

        $this->enableObservers();
        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Dataset::saved(function (Dataset $dataset) use ($datasetId) {
            DB::table('dataset_versions')
                ->where('dataset_id', $datasetId)
                ->where('version', 1)
                ->update(['active_date' => '2026-11-20 09:00:00']);
        });

        $metadata = $this->getMetadataV2p0();
        $metadata['metadata']['summary']['title'] = 'Concurrent draft';

        try {
            $this->service()->update(
                Dataset::findOrFail($datasetId),
                ['metadata' => $metadata, 'status' => Dataset::STATUS_DRAFT],
                $user->id,
                Dataset::ORIGIN_MANUAL,
                Partner::HDRUK,
                targetGwdmVersion: '2.0',
            );
            $this->fail('Expected the update to refuse to replace a version published mid-save.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('was published while this save was in progress', $e->getMessage());
        }

        $after = DatasetVersion::where('dataset_id', $datasetId)->where('version', 1)->firstOrFail()->getAttributes();
        $this->assertSame(1, DatasetVersion::where('dataset_id', $datasetId)->count());
        $this->assertSame(
            array_diff_key($before, ['active_date' => true]),
            array_diff_key($after, ['active_date' => true]),
        );
    }

    public function test_a_dataset_is_saved_at_the_gwdm_version_its_caller_asks_for(): void
    {
        request()->headers->set('x-gwdm-version', '2.0');
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

        $this->service()->update(
            dataset: Dataset::findOrFail($created['dataset_id']),
            input: ['metadata' => $this->getMetadataV2p1(), 'status' => Dataset::STATUS_ACTIVE],
            userId: $user->id,
            createOrigin: Dataset::ORIGIN_MANUAL,
            partnerContext: null,
            targetGwdmVersion: '2.1',
        );

        $saved = DatasetVersion::where('dataset_id', $created['dataset_id'])->where('version', 2)->firstOrFail();
        $this->assertSame('2.1', $saved->gwdm_version);
        $this->assertNull($saved->patch, 'a schema change from version 1 is stored as a full copy');
    }

    public function test_update_with_cohort_discovery_sets_it(): void
    {
        $team = Team::first();
        $user = User::first();
        $metadata = $this->getMetadataV2p0();

        $created = $this->service()->create(
            [
                'metadata' => $metadata,
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'is_cohort_discovery' => false,
            ],
            $team,
            null,
            null,
            targetGwdmVersion: '2.0',
        );

        $this->service()->update(
            Dataset::findOrFail($created['dataset_id']),
            [
                'metadata' => $metadata,
                'status' => Dataset::STATUS_ACTIVE,
                'is_cohort_discovery' => true,
            ],
            $user->id,
            Dataset::ORIGIN_MANUAL,
            Partner::HDRUK,
            targetGwdmVersion: '2.0',
        );

        $this->assertTrue((bool) Dataset::findOrFail($created['dataset_id'])->is_cohort_discovery);
    }
}
