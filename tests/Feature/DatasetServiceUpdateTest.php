<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Enums\Partner;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
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
        request()->headers->set('x-gwdm-version', '2.0');
    }

    private function service(): DatasetService
    {
        return app(DatasetService::class);
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
        );

        $this->assertTrue((bool) Dataset::findOrFail($created['dataset_id'])->is_cohort_discovery);
    }
}
