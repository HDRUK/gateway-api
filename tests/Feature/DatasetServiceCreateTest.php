<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Services\Gwdm\SavedBy;
use App\Enums\DatasetOrigin;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class DatasetServiceCreateTest extends TestCase
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

    public function test_a_dataset_created_as_published_has_a_published_first_version(): void
    {
        $this->assertNotNull($this->firstVersionOfDatasetCreatedAs(Dataset::STATUS_ACTIVE)->active_date);
    }

    public function test_a_dataset_created_as_a_draft_has_an_unpublished_first_version(): void
    {
        $this->assertNull($this->firstVersionOfDatasetCreatedAs(Dataset::STATUS_DRAFT)->active_date);
    }

    public function test_a_dataset_is_created_at_the_gwdm_version_its_caller_asks_for(): void
    {
        request()->headers->set('x-gwdm-version', '2.0');
        $team = Team::first();
        $user = User::first();

        $created = app(DatasetService::class)->create(
            input: [
                'metadata' => $this->getMetadataV2p1(),
                'status' => Dataset::STATUS_ACTIVE,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            team: $team,
            savedBy: SavedBy::person($user->id, DatasetOrigin::MANUAL),
            inputSchema: null,
            inputVersion: null,
            targetGwdmVersion: '2.1',
        );

        $this->assertSame(
            '2.1',
            DatasetVersion::where('dataset_id', $created['dataset_id'])->where('version', 1)->firstOrFail()->gwdm_version,
        );
    }

    private function firstVersionOfDatasetCreatedAs(string $status): DatasetVersion
    {
        $team = Team::first();
        $user = User::first();

        $created = app(DatasetService::class)->create(
            [
                'metadata' => $this->getMetadataV2p0(),
                'status' => $status,
                'user_id' => $user->id,
                'team_id' => $team->id,
                'create_origin' => Dataset::ORIGIN_MANUAL,
            ],
            $team,
            SavedBy::person($user->id, DatasetOrigin::MANUAL),
            null,
            null,
            targetGwdmVersion: '2.0',
        );

        return DatasetVersion::where('dataset_id', $created['dataset_id'])->where('version', 1)->firstOrFail();
    }
}
