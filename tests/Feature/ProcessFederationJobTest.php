<?php

namespace Tests\Feature;

use App\Jobs\ProcessFederation;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class ProcessFederationJobTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const BASE_URL = 'https://test-federation.example.com';
    private const DATASETS_PATH = '/api/v1/datasets';
    private const DATASET_PATH = '/api/v1/datasets/{id}';

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function mockGsms(): void
    {
        $this->mock(GoogleSecretManagerService::class);
    }

    private function makeFederation(): array
    {
        $team = Team::factory()->create();
        $federation = Federation::factory()->create([
            'auth_type' => 'NO_AUTH',
            'endpoint_baseurl' => self::BASE_URL,
            'endpoint_datasets' => self::DATASETS_PATH,
            'endpoint_dataset' => self::DATASET_PATH,
            'enabled' => true,
            'tested' => true,
            'is_running' => false,
        ]);
        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);
        return [$team, $federation];
    }

    private function catalogueUrlPattern(): string
    {
        return self::BASE_URL . self::DATASETS_PATH . '*';
    }

    private function datasetUrlPattern(string $pid): string
    {
        return self::BASE_URL . str_replace('{id}', $pid, self::DATASET_PATH) . '*';
    }

    private function fakeRemoteCatalogue(array $items): void
    {
        Http::fake([
            $this->catalogueUrlPattern() => Http::response(['items' => $items], 200),
        ]);
    }

    private function makeGmiDataset(int $teamId, string $pid, string $status = Dataset::STATUS_ACTIVE): Dataset
    {
        return Dataset::create([
            'user_id' => $this->currentUser['id'],
            'pid' => $pid,
            'team_id' => $teamId,
            'create_origin' => Dataset::ORIGIN_GMI,
            'status' => $status,
        ]);
    }

    public function test_job_dispatched_to_federation_queue(): void
    {
        Queue::fake();
        [, $federation] = $this->makeFederation();

        ProcessFederation::dispatch($federation);

        Queue::assertPushedOn('federation', ProcessFederation::class);
    }

    public function test_job_timeout_is_120_seconds(): void
    {
        [, $federation] = $this->makeFederation();

        $job = new ProcessFederation($federation);

        $this->assertSame(120, $job->timeout);
    }

    public function test_job_has_three_retries(): void
    {
        [, $federation] = $this->makeFederation();

        $job = new ProcessFederation($federation);

        $this->assertSame(3, $job->tries);
    }

    public function test_is_running_cleared_after_successful_handle(): void
    {
        [, $federation] = $this->makeFederation();
        $this->mockGsms();
        $this->fakeRemoteCatalogue([]);

        (new ProcessFederation($federation))->handle();

        $this->assertFalse($federation->fresh()->is_running);
    }

    public function test_is_running_cleared_when_remote_returns_error(): void
    {
        [, $federation] = $this->makeFederation();
        $this->mockGsms();

        Http::fake([
            $this->catalogueUrlPattern() => Http::response([], 503),
        ]);

        try {
            (new ProcessFederation($federation))->handle();
        } catch (\RuntimeException) {
            // expected — we're verifying is_running below, not the exception itself
        }

        $this->assertFalse($federation->fresh()->is_running);
    }

    public function test_non_200_remote_catalogue_throws_runtime_exception(): void
    {
        [, $federation] = $this->makeFederation();
        $this->mockGsms();

        Http::fake([
            $this->catalogueUrlPattern() => Http::response(['error' => 'unavailable'], 503),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/non-200 status 503/');

        (new ProcessFederation($federation))->handle();
    }

    public function test_runtime_exception_message_includes_url_and_body(): void
    {
        [, $federation] = $this->makeFederation();
        $this->mockGsms();

        Http::fake([
            $this->catalogueUrlPattern() => Http::response('Gateway Timeout', 504),
        ]);

        try {
            (new ProcessFederation($federation))->handle();
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('504', $e->getMessage());
            $this->assertStringContainsString(self::BASE_URL, $e->getMessage());
        }
    }

    public function test_local_gmi_dataset_absent_from_remote_is_archived(): void
    {
        [$team, $federation] = $this->makeFederation();
        $this->mockGsms();

        // "shared" exists in both remote and local — prevents early abort, no create/archive for it
        $this->makeGmiDataset($team->id, 'shared-pid');
        $toArchive = $this->makeGmiDataset($team->id, 'to-archive-pid');

        Http::fake([
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'shared-pid', 'version' => '1.0']],
            ], 200),
            $this->datasetUrlPattern('shared-pid') => Http::response([], 404), // skip update
        ]);

        (new ProcessFederation($federation))->handle();

        $this->assertSame(Dataset::STATUS_ARCHIVED, $toArchive->fresh()->status);
    }

    public function test_non_gmi_dataset_absent_from_remote_is_not_archived(): void
    {
        [$team, $federation] = $this->makeFederation();
        $this->mockGsms();

        $this->makeGmiDataset($team->id, 'shared-pid');

        $manualDataset = Dataset::create([
            'user_id' => $this->currentUser['id'],
            'pid' => 'manual-pid',
            'team_id' => $team->id,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        Http::fake([
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'shared-pid', 'version' => '1.0']],
            ], 200),
            $this->datasetUrlPattern('shared-pid') => Http::response([], 404),
        ]);

        (new ProcessFederation($federation))->handle();

        $this->assertSame(Dataset::STATUS_ACTIVE, $manualDataset->fresh()->status);
    }

    public function test_update_skips_gracefully_when_dataset_version_is_missing(): void
    {
        [$team, $federation] = $this->makeFederation();
        $this->mockGsms();

        // Dataset exists locally but has NO DatasetVersion record
        $this->makeGmiDataset($team->id, 'no-version-pid');

        Http::fake([
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'no-version-pid', 'version' => '2.0']],
            ], 200),
            $this->datasetUrlPattern('no-version-pid') => Http::response(['metadata' => []], 200),
        ]);

        // Should complete without throwing
        (new ProcessFederation($federation))->handle();

        $this->assertSame(
            Dataset::STATUS_ACTIVE,
            Dataset::where('pid', 'no-version-pid')->first()->status
        );
    }

    public function test_update_skips_gracefully_when_version_key_is_absent_from_metadata(): void
    {
        Queue::fake();

        [$team, $federation] = $this->makeFederation();
        $this->mockGsms();

        $dataset = $this->makeGmiDataset($team->id, 'bad-meta-pid');

        // DatasetVersion exists but has no 'required.version' path in metadata
        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'metadata' => ['metadata' => ['required' => ['title' => 'No version here']]],
            'version' => 1,
            'provider_team_id' => $team->id,
            'application_type' => 'dataset',
        ]);

        Http::fake([
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'bad-meta-pid', 'version' => '2.0']],
            ], 200),
            $this->datasetUrlPattern('bad-meta-pid') => Http::response(['metadata' => []], 200),
        ]);

        (new ProcessFederation($federation))->handle();

        $this->assertSame(
            Dataset::STATUS_ACTIVE,
            Dataset::where('pid', 'bad-meta-pid')->first()->status
        );
    }

    public function test_create_exception_catch_block_logs_real_message(): void
    {
        [$team, $federation] = $this->makeFederation();

        $mockGsms = $this->createMock(GoogleSecretManagerService::class);
        $mockGmi = $this->createMock(GatewayMetadataIngestionService::class);
        $mockGmi->method('getTeam')->willReturn($team->id);
        $mockGmi->method('storeMetadata')
            ->willThrowException(new \Exception('meaningful error from storeMetadata'));

        Http::fake([
            $this->datasetUrlPattern('new-pid') => Http::response(['metadata' => []], 200),
        ]);

        Log::spy();

        // Use a concrete class that exercises the trait method directly
        $trait = new class () {
            use \App\Traits\GatewayMetadataIngestionTrait;
        };

        $trait->createLocalDatasetsMissingFromRemoteCatalogue(
            collect([]),
            collect(['new-pid' => ['persistentId' => 'new-pid', 'version' => '1.0']]),
            $federation,
            $mockGsms,
            $mockGmi
        );

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(
                fn (string $msg) =>
                str_contains($msg, 'meaningful error from storeMetadata')
            );
    }

    public function test_gmi_dataset_from_another_team_is_never_archived(): void
    {
        [$team, $federation] = $this->makeFederation();
        $this->mockGsms();
        $otherTeam = Team::factory()->create();

        // "shared" keeps the remote non-empty so archiving runs
        $this->makeGmiDataset($team->id, 'shared-pid');

        // GMI dataset on a different team — must never be touched by this federation
        $otherTeamDataset = $this->makeGmiDataset($otherTeam->id, 'other-team-pid');

        Http::fake([
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'shared-pid', 'version' => '1.0']],
            ], 200),
            $this->datasetUrlPattern('shared-pid') => Http::response([], 404),
        ]);

        (new ProcessFederation($federation))->handle();

        $this->assertSame(Dataset::STATUS_ACTIVE, $otherTeamDataset->fresh()->status);
    }
}
