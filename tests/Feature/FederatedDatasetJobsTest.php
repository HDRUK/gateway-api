<?php

namespace Tests\Feature;

use App\Jobs\ArchiveFederatedDatasetsChunk;
use App\Jobs\CreateFederatedDatasetJob;
use App\Jobs\UpdateFederatedDatasetJob;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use App\Services\GatewayMetadataIngestionService;
use App\Services\Gwdm\GwdmMetadataHandler;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederatedDatasetJobsTest extends TestCase
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

    private function datasetUrlPattern(string $pid): string
    {
        return self::BASE_URL . str_replace('{id}', $pid, self::DATASET_PATH) . '*';
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

    public function test_create_job_creates_dataset_and_records_success_history(): void
    {
        [$team, $federation] = $this->makeFederation();

        Http::fake([
            $this->datasetUrlPattern('new-pid') => Http::response($this->getMetadata(), 200),
        ]);

        $mockGmi = $this->createMock(GatewayMetadataIngestionService::class);
        $mockGmi->method('getTeam')->willReturn($team->id);
        $mockGmi->method('storeMetadata')->willReturn(true);

        $job = new CreateFederatedDatasetJob(
            $federation,
            'new-pid',
            ['persistentId' => 'new-pid', 'version' => '1.0'],
            'job-uuid-chunk-create',
            1,
        );

        $job->handle($mockGmi, app(GwdmMetadataHandler::class));

        $this->assertDatabaseHas('federation_job_runs', [
            'pid' => 'new-pid',
            'job_uuid' => 'job-uuid-chunk-create',
            'status' => 1,
        ]);
    }

    public function test_create_job_skips_and_does_not_call_remote_when_dataset_already_exists(): void
    {
        [$team, $federation] = $this->makeFederation();
        $this->makeGmiDataset($team->id, 'already-there');

        Http::fake([
            $this->datasetUrlPattern('already-there') => Http::response($this->getMetadata(), 200),
        ]);

        $job = new CreateFederatedDatasetJob(
            $federation,
            'already-there',
            ['persistentId' => 'already-there', 'version' => '1.0'],
            'job-uuid-chunk-create-skip',
            1,
        );

        $job->handle(app(GatewayMetadataIngestionService::class), app(GwdmMetadataHandler::class));

        Http::assertNothingSent();
        $this->assertDatabaseMissing('federation_job_runs', ['pid' => 'already-there']);
    }

    public function test_update_job_updates_dataset_and_records_success_history(): void
    {
        [$team, $federation] = $this->makeFederation();
        $dataset = $this->makeGmiDataset($team->id, 'existing-pid');
        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'metadata' => ['metadata' => ['required' => ['version' => '1.0']]],
            'version' => 1,
            'provider_team_id' => $team->id,
            'application_type' => 'dataset',
        ]);

        Http::fake([
            $this->datasetUrlPattern('existing-pid') => Http::response($this->getMetadata(), 200),
        ]);

        $job = new UpdateFederatedDatasetJob(
            $federation,
            'existing-pid',
            ['persistentId' => 'existing-pid', 'version' => '2.0'],
            $dataset,
            'job-uuid-chunk-update',
            1,
        );

        $job->handle(app(GatewayMetadataIngestionService::class));

        $this->assertDatabaseHas('federation_job_runs', [
            'pid' => 'existing-pid',
            'job_uuid' => 'job-uuid-chunk-update',
            'status' => 1,
        ]);
    }

    public function test_archive_chunk_archives_every_pid_in_its_chunk(): void
    {
        [$team, $federation] = $this->makeFederation();
        $first = $this->makeGmiDataset($team->id, 'archive-me-1');
        $second = $this->makeGmiDataset($team->id, 'archive-me-2');

        $job = new ArchiveFederatedDatasetsChunk($federation, ['archive-me-1', 'archive-me-2']);

        $job->handle(app(GatewayMetadataIngestionService::class));

        $this->assertSame(Dataset::STATUS_ARCHIVED, $first->fresh()->status);
        $this->assertSame(Dataset::STATUS_ARCHIVED, $second->fresh()->status);
    }

    public function test_archive_chunk_skips_pids_with_no_matching_local_dataset(): void
    {
        [, $federation] = $this->makeFederation();

        $job = new ArchiveFederatedDatasetsChunk($federation, ['never-existed']);

        $job->handle(app(GatewayMetadataIngestionService::class));

        $this->assertDatabaseMissing('datasets', ['pid' => 'never-existed']);
    }
}
