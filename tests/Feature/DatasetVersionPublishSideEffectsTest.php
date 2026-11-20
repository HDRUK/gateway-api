<?php

namespace Tests\Feature;

use App\Enums\Partner;
use App\Jobs\ExtractProjectGrantsFromMetadata;
use App\Jobs\ExtractPublicationsFromMetadata;
use App\Jobs\ExtractToolsFromMetadata;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use App\Services\DatasetService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class DatasetVersionPublishSideEffectsTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const EXTRACTION_JOBS = [
        ExtractToolsFromMetadata::class,
        ExtractPublicationsFromMetadata::class,
        ExtractProjectGrantsFromMetadata::class,
    ];

    protected function setUp(): void
    {
        $this->commonSetUp();
        request()->headers->set('x-gwdm-version', '2.0');
    }

    public function test_publishing_a_draft_in_place_queues_metadata_extraction(): void
    {
        $team = Team::first();
        $user = User::first();
        Queue::fake();

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
        );

        foreach (self::EXTRACTION_JOBS as $job) {
            Queue::assertNotPushed($job);
        }

        app(DatasetService::class)->update(
            Dataset::findOrFail($created['dataset_id']),
            ['metadata' => $this->getMetadataV2p0(), 'status' => Dataset::STATUS_ACTIVE],
            $user->id,
            Dataset::ORIGIN_MANUAL,
            Partner::HDRUK,
        );

        $this->assertSame(1, DatasetVersion::where('dataset_id', $created['dataset_id'])->count());
        foreach (self::EXTRACTION_JOBS as $job) {
            Queue::assertPushed($job);
        }
    }
}
