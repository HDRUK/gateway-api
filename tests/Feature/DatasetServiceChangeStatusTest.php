<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class DatasetServiceChangeStatusTest extends TestCase
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

    public function test_a_working_copy_published_by_another_request_keeps_its_first_publish_date(): void
    {
        $team = Team::first();
        $user = User::first();
        $created = app(DatasetService::class)->create(
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

        $this->enableObservers();
        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();
        Dataset::saved(function () use ($datasetId) {
            DB::table('dataset_versions')
                ->where('dataset_id', $datasetId)
                ->where('version', 1)
                ->update(['active_date' => '2026-11-20 09:00:00']);
        });

        app(DatasetService::class)->changeStatus(Dataset::findOrFail($datasetId), Dataset::STATUS_ACTIVE);

        $this->assertSame(
            '2026-11-20 09:00:00',
            DatasetVersion::where('dataset_id', $datasetId)->where('version', 1)->firstOrFail()->getRawOriginal('active_date'),
        );
    }
}
