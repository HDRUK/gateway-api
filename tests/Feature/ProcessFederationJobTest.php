<?php

namespace Tests\Feature;

use App\Events\FederationProcessed;
use App\Jobs\ProcessFederation;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;
use App\Services\Gwdm\GwdmMetadataHandler;
use Illuminate\Support\Facades\Event;
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

    private function makeAuthenticatedFederation(string $authType, string $secretLocation = 'projects/test/secrets/federation-token'): array
    {
        $team = Team::factory()->create();
        $federation = Federation::factory()->create([
            'auth_type' => $authType,
            'auth_secret_key_location' => $secretLocation,
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

    /**
     * Simulates a real remote server that enforces auth (BEARER or API_KEY)
     * on every request — both the initial catalogue-list connection and any
     * subsequent per-dataset lookups. Requests without the exact expected
     * credential are rejected with 401, exactly like a real auth-gated
     * endpoint would.
     */
    private function fakeAuthenticatingRemoteServer(
        string $authType,
        string $expectedCredential,
        array $catalogueItems,
        array $datasetBodiesByPid = [],
    ): void {
        Http::fake(function ($request) use ($authType, $expectedCredential, $catalogueItems, $datasetBodiesByPid) {
            // Only intercept calls to the simulated federation host — let any
            // other request (e.g. the real internal TRASER/MMC translation
            // call) fall through to whatever stub already handles it.
            if (parse_url($request->url(), PHP_URL_HOST) !== parse_url(self::BASE_URL, PHP_URL_HOST)) {
                return null;
            }

            $receivedCredential = match ($authType) {
                'BEARER' => $request->header('Authorization')[0] ?? null,
                'API_KEY' => $request->header('apikey')[0] ?? null,
            };
            $expectedHeaderValue = $authType === 'BEARER' ? "Bearer {$expectedCredential}" : $expectedCredential;

            if ($receivedCredential !== $expectedHeaderValue) {
                return Http::response(['error' => 'unauthorized'], 401);
            }

            $path = parse_url($request->url(), PHP_URL_PATH);

            if ($path === self::DATASETS_PATH) {
                return Http::response(['items' => $catalogueItems], 200);
            }

            foreach ($datasetBodiesByPid as $pid => $body) {
                if (str_ends_with($path, "/{$pid}")) {
                    return Http::response($body, 200);
                }
            }

            return Http::response(['error' => 'not found'], 404);
        });
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

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

        $this->assertFalse($federation->fresh()->is_running);
    }

    public function test_error_cleared_when_remote_returns_empty_items_after_prior_failure(): void
    {
        [, $federation] = $this->makeFederation();
        $federation->update([
            'error' => true,
            'error_text' => 'a previous connection failure',
        ]);
        $this->mockGsms();
        $this->fakeRemoteCatalogue([]);

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

        $fresh = $federation->fresh();
        $this->assertFalse($fresh->error);
        $this->assertNull($fresh->error_text);
    }

    public function test_run_with_per_item_history_failures_records_error_and_skips_success_event(): void
    {
        [, $federation] = $this->makeFederation();
        $this->mockGsms();

        Http::fake([
            $this->datasetUrlPattern('some-pid') => Http::response(['metadata' => []], 404),
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'some-pid', 'version' => '1.0']],
            ], 200),
        ]);

        Event::fake([FederationProcessed::class]);

        // sendToHistory(status: 0) is what per-item create/update/archive
        // failures actually call internally — force it here rather than
        // reproducing a real translation failure, so this test exercises
        // ProcessFederation's branch on hadHistoryFailures() in isolation.
        $job = new class ($federation) extends ProcessFederation {
            public function hadHistoryFailures(): bool
            {
                return true;
            }
        };

        $job->handle(app(GwdmMetadataHandler::class));

        Event::assertNotDispatched(FederationProcessed::class);

        $fresh = $federation->fresh();
        $this->assertFalse($fresh->is_running);
        $this->assertTrue($fresh->error);
        $this->assertStringContainsString('job', $fresh->error_text);
    }

    public function test_is_running_cleared_when_remote_returns_error(): void
    {
        [, $federation] = $this->makeFederation();
        $this->mockGsms();

        Http::fake([
            $this->catalogueUrlPattern() => Http::response([], 503),
        ]);

        try {
            (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));
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

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));
    }

    public function test_runtime_exception_message_includes_url_and_body(): void
    {
        [, $federation] = $this->makeFederation();
        $this->mockGsms();

        Http::fake([
            $this->catalogueUrlPattern() => Http::response('Gateway Timeout', 504),
        ]);

        try {
            (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));
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

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

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

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

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
        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

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

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

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
            $mockGmi,
            'job_uuid',
            1,
            app(GwdmMetadataHandler::class),
        );

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(
                fn (string $msg) =>
                str_contains($msg, 'meaningful error from storeMetadata')
            );
    }

    public function test_create_exception_stores_safe_message_not_raw_exception(): void
    {
        [$team, $federation] = $this->makeFederation();

        $sensitiveMessage = 'projects/987760029877/secrets/prod-mfs-17: binding failed for segment {project=*}';

        $mockGsms = $this->createMock(GoogleSecretManagerService::class);
        $mockGmi  = $this->createMock(GatewayMetadataIngestionService::class);
        $mockGmi->method('getTeam')->willReturn($team->id);
        $mockGmi->method('storeMetadata')
            ->willThrowException(new \Exception($sensitiveMessage));

        Http::fake([
            $this->datasetUrlPattern('new-pid') => Http::response(['metadata' => []], 200),
        ]);

        $trait = new class () {
            use \App\Traits\GatewayMetadataIngestionTrait;
        };

        $trait->createLocalDatasetsMissingFromRemoteCatalogue(
            collect([]),
            collect(['new-pid' => ['persistentId' => 'new-pid', 'version' => '1.0']]),
            $federation,
            $mockGsms,
            $mockGmi,
            'test-job-uuid',
            1,
            app(GwdmMetadataHandler::class),
        );

        $record = \App\Models\FederationJobRun::where('pid', 'new-pid')->first();
        $this->assertNotNull($record);
        $this->assertSame(0, $record->status);
        $this->assertStringNotContainsString($sensitiveMessage, $record->details['message']);
        $this->assertStringContainsString('unexpected error', $record->details['message']);
        $this->assertStringContainsString('test-job-uuid', $record->details['message']);
    }

    public function test_update_exception_stores_safe_message_not_raw_exception(): void
    {
        [$team, $federation] = $this->makeFederation();

        $sensitiveMessage = 'projects/987760029877/secrets/prod-mfs-17: binding failed for segment {project=*}';

        $dataset = $this->makeGmiDataset($team->id, 'existing-pid');

        $mockGsms = $this->createMock(GoogleSecretManagerService::class);
        $mockGmi  = $this->createMock(GatewayMetadataIngestionService::class);
        $mockGmi->method('getTeam')->willReturn($team->id);

        // Override makeDatasetUrl to throw with the sensitive message — this fires inside
        // the try block, reliably triggering the catch without needing an Http fake.
        $sensitiveMessageCopy = $sensitiveMessage;
        $trait = new class ($sensitiveMessageCopy) {
            use \App\Traits\GatewayMetadataIngestionTrait;

            private string $boom;

            public function __construct(string $boom)
            {
                $this->boom = $boom;
            }

            public function makeDatasetUrl(\App\Models\Federation $federation, array $data): string
            {
                throw new \RuntimeException($this->boom);
            }
        };

        $trait->updateLocalDatasetsChangedInRemoteCatalogue(
            collect([$dataset->pid => $dataset]),
            collect(['existing-pid' => ['persistentId' => 'existing-pid', 'version' => '2.0']]),
            $federation,
            $mockGsms,
            $mockGmi,
            'test-job-uuid',
            1
        );

        $record = \App\Models\FederationJobRun::where('pid', 'existing-pid')->first();
        $this->assertNotNull($record);
        $this->assertSame(0, $record->status);
        $this->assertStringNotContainsString($sensitiveMessage, $record->details['message']);
        $this->assertStringContainsString('unexpected error', $record->details['message']);
        $this->assertStringContainsString('test-job-uuid', $record->details['message']);
    }

    public function test_create_dataset_request_includes_auth_header(): void
    {
        $team = Team::factory()->create();
        $federation = Federation::factory()->create([
            'auth_type' => 'BEARER',
            'auth_secret_key_location' => 'projects/test/secrets/federation-token',
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

        $this->mock(GoogleSecretManagerService::class, function ($mock) {
            $mock->shouldReceive('getSecret')->andReturn(json_encode(['bearer_token' => 'test-token-value']));
        });

        Http::fake([
            // Dataset-specific pattern registered before the catalogue wildcard —
            // Http::fake matches in registration order, and the catalogue pattern's
            // trailing '*' would otherwise also swallow this more specific URL.
            $this->datasetUrlPattern('new-pid') => Http::response(['metadata' => []], 404),
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'new-pid', 'version' => '1.0']],
            ], 200),
        ]);

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/datasets/new-pid')
                && $request->hasHeader('Authorization', 'Bearer test-token-value');
        });
    }

    public function test_update_dataset_request_includes_auth_header(): void
    {
        [$team, $federation] = $this->makeFederation();
        $federation->update([
            'auth_type' => 'BEARER',
            'auth_secret_key_location' => 'projects/test/secrets/federation-token',
        ]);

        $dataset = $this->makeGmiDataset($team->id, 'existing-pid');
        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'metadata' => ['metadata' => ['required' => ['version' => '1.0']]],
            'version' => 1,
            'provider_team_id' => $team->id,
            'application_type' => 'dataset',
        ]);

        $this->mock(GoogleSecretManagerService::class, function ($mock) {
            $mock->shouldReceive('getSecret')->andReturn(json_encode(['bearer_token' => 'test-token-value']));
        });

        Http::fake([
            $this->datasetUrlPattern('existing-pid') => Http::response(['metadata' => []], 404),
            $this->catalogueUrlPattern() => Http::response([
                'items' => [['persistentId' => 'existing-pid', 'version' => '2.0']],
            ], 200),
        ]);

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/datasets/existing-pid')
                && $request->hasHeader('Authorization', 'Bearer test-token-value');
        });
    }

    public function test_full_sync_succeeds_against_simulated_authenticating_server(): void
    {
        [, $federation] = $this->makeAuthenticatedFederation('BEARER');

        $this->mock(GoogleSecretManagerService::class, function ($mock) {
            $mock->shouldReceive('getSecret')->andReturn(json_encode(['bearer_token' => 'correct-token']));
        });

        // The simulated server only accepts requests carrying the exact bearer
        // token above — it 401s the initial catalogue connection AND any
        // subsequent per-dataset lookup if either is missing the header. The
        // per-dataset response is a benign 404 ("nothing to translate") so
        // this test stays focused on the auth gate rather than exercising the
        // unrelated metadata-translation/dataset-creation internals.
        $this->fakeAuthenticatingRemoteServer(
            authType: 'BEARER',
            expectedCredential: 'correct-token',
            catalogueItems: [['persistentId' => 'server-verified-pid', 'version' => '1.0']],
            datasetBodiesByPid: [],
        );

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

        // Both the catalogue call and the per-dataset call must have carried
        // valid auth — if either had been sent without the header, the
        // simulated server would have 401'd it instead of responding 404.
        Http::assertSent(fn ($request) => $request->url() === self::BASE_URL . self::DATASETS_PATH
            && $request->hasHeader('Authorization', 'Bearer correct-token'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/server-verified-pid')
            && $request->hasHeader('Authorization', 'Bearer correct-token'));

        // No auth failure was recorded for this dataset.
        $this->assertDatabaseMissing('federation_job_runs', ['pid' => 'server-verified-pid', 'status' => 0]);
    }

    public function test_initial_catalogue_connection_is_rejected_by_server_on_bad_bearer_auth(): void
    {
        [, $federation] = $this->makeAuthenticatedFederation('BEARER');

        // Secret store hands back a token that does NOT match what the
        // simulated server expects (e.g. a stale/rotated secret).
        $this->mock(GoogleSecretManagerService::class, function ($mock) {
            $mock->shouldReceive('getSecret')->andReturn(json_encode(['bearer_token' => 'stale-token']));
        });

        $this->fakeAuthenticatingRemoteServer(
            authType: 'BEARER',
            expectedCredential: 'correct-token',
            catalogueItems: [['persistentId' => 'server-verified-pid', 'version' => '1.0']],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/non-200 status 401/');

        try {
            (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));
        } finally {
            $this->assertDatabaseMissing('datasets', ['pid' => 'server-verified-pid']);
        }
    }

    public function test_full_sync_succeeds_against_simulated_server_with_api_key_auth(): void
    {
        [, $federation] = $this->makeAuthenticatedFederation('API_KEY');

        $this->mock(GoogleSecretManagerService::class, function ($mock) {
            $mock->shouldReceive('getSecret')->andReturn(json_encode(['api_key' => 'correct-api-key']));
        });

        // Same simulated auth gate as the bearer-token test, but checking the
        // 'apikey' header instead of 'Authorization' — this is what
        // determineAuthType() sends for API_KEY federations.
        $this->fakeAuthenticatingRemoteServer(
            authType: 'API_KEY',
            expectedCredential: 'correct-api-key',
            catalogueItems: [['persistentId' => 'server-verified-pid', 'version' => '1.0']],
            datasetBodiesByPid: [],
        );

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

        Http::assertSent(fn ($request) => $request->url() === self::BASE_URL . self::DATASETS_PATH
            && $request->hasHeader('apikey', 'correct-api-key'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/server-verified-pid')
            && $request->hasHeader('apikey', 'correct-api-key'));

        $this->assertDatabaseMissing('federation_job_runs', ['pid' => 'server-verified-pid', 'status' => 0]);
    }

    public function test_initial_catalogue_connection_is_rejected_by_server_on_bad_api_key_auth(): void
    {
        [, $federation] = $this->makeAuthenticatedFederation('API_KEY');

        // Secret store hands back an API key that does NOT match what the
        // simulated server expects.
        $this->mock(GoogleSecretManagerService::class, function ($mock) {
            $mock->shouldReceive('getSecret')->andReturn(json_encode(['api_key' => 'stale-api-key']));
        });

        $this->fakeAuthenticatingRemoteServer(
            authType: 'API_KEY',
            expectedCredential: 'correct-api-key',
            catalogueItems: [['persistentId' => 'server-verified-pid', 'version' => '1.0']],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/non-200 status 401/');

        try {
            (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));
        } finally {
            $this->assertDatabaseMissing('datasets', ['pid' => 'server-verified-pid']);
        }
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

        (new ProcessFederation($federation))->handle(app(GwdmMetadataHandler::class));

        $this->assertSame(Dataset::STATUS_ACTIVE, $otherTeamDataset->fresh()->status);
    }
}
