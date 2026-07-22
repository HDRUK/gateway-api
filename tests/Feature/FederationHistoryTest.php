<?php

namespace Tests\Feature;

use App\Models\Federation;
use App\Models\FederationJobRun;
use App\Models\Team;
use App\Models\TeamHasFederation;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederationHistoryTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeFederation(): array
    {
        $team = Team::factory()->create();
        $federation = Federation::factory()->create([
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

    private function makeRun(Team $team, Federation $federation, string $jobUuid, string $pid, ?int $status, $message, string $createdAt): FederationJobRun
    {
        $run = FederationJobRun::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
            'job_uuid' => $jobUuid,
            'pid' => $pid,
            'status' => $status,
            'details' => ['message' => $message],
            'job_attempts' => 1,
        ]);

        FederationJobRun::where('id', $run->id)->update(['created_at' => $createdAt]);

        return $run->fresh();
    }

    private function historyUrl(int $teamId, int $federationId): string
    {
        return "api/v1/teams/{$teamId}/federations/{$federationId}/history";
    }

    public function test_executions_are_listed_most_recent_first_with_correct_pagination(): void
    {
        [$team, $federation] = $this->makeFederation();

        $this->makeRun($team, $federation, 'uuid-older', 'pid-1', 1, 'CREATED', now()->subDays(2)->toDateTimeString());
        $this->makeRun($team, $federation, 'uuid-newer', 'pid-2', 1, 'CREATED', now()->subDay()->toDateTimeString());

        $response = $this->get($this->historyUrl($team->id, $federation->id), $this->header);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => ['job_uuid', 'started_at', 'finished_at', 'status', 'message', 'failed_datasets'],
                ],
                'total',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertSame(2, $content['total']);
        $this->assertSame('uuid-newer', $content['data'][0]['job_uuid']);
        $this->assertSame('success', $content['data'][0]['status']);
        $this->assertNull($content['data'][0]['message']);
        $this->assertSame([], $content['data'][0]['failed_datasets']);
        $this->assertSame('uuid-older', $content['data'][1]['job_uuid']);
    }

    public function test_single_dataset_failure_surfaces_its_normalized_message(): void
    {
        [$team, $federation] = $this->makeFederation();

        $this->makeRun(
            $team,
            $federation,
            'uuid-failed',
            'pid-err',
            0,
            'An unexpected error occurred while creating dataset pid-err. Please contact support and reference job: uuid-failed',
            now()->toDateTimeString()
        );

        $response = $this->get($this->historyUrl($team->id, $federation->id), $this->header);
        $content = $response->decodeResponseJson();

        $this->assertSame('failed', $content['data'][0]['status']);
        $this->assertSame(
            'An unexpected error occurred while creating dataset pid-err. Please contact support and reference job: uuid-failed',
            $content['data'][0]['message']
        );
        $this->assertCount(1, $content['data'][0]['failed_datasets']);
        $this->assertSame('pid-err', $content['data'][0]['failed_datasets'][0]['pid']);
        $this->assertSame(
            'An unexpected error occurred while creating dataset pid-err. Please contact support and reference job: uuid-failed',
            $content['data'][0]['failed_datasets'][0]['message']
        );
    }

    public function test_multiple_dataset_failures_collapse_summary_but_keep_full_detail(): void
    {
        [$team, $federation] = $this->makeFederation();

        $this->makeRun($team, $federation, 'uuid-multi', 'pid-1', 0, 'boom 1', now()->toDateTimeString());
        $this->makeRun($team, $federation, 'uuid-multi', 'pid-2', 0, 'boom 2', now()->toDateTimeString());
        $this->makeRun($team, $federation, 'uuid-multi', 'pid-3', 1, 'CREATED', now()->toDateTimeString());

        $response = $this->get($this->historyUrl($team->id, $federation->id), $this->header);
        $content = $response->decodeResponseJson();

        $this->assertSame('failed', $content['data'][0]['status']);
        $this->assertSame('2 of 3 datasets failed', $content['data'][0]['message']);

        $failedDatasets = $content['data'][0]['failed_datasets'];
        $this->assertCount(2, $failedDatasets);
        $pids = array_column($failedDatasets, 'pid');
        $messages = array_column($failedDatasets, 'message');
        $this->assertContains('pid-1', $pids);
        $this->assertContains('pid-2', $pids);
        $this->assertContains('boom 1', $messages);
        $this->assertContains('boom 2', $messages);
    }

    public function test_nested_traser_validation_errors_are_normalized_to_readable_text(): void
    {
        [$team, $federation] = $this->makeFederation();

        $this->makeRun(
            $team,
            $federation,
            'uuid-traser',
            'pid-traser',
            0,
            [[
                'name' => 'HDRUK',
                'version' => '2.0.2',
                'errors' => [['message' => 'must NOT have additional properties']],
            ]],
            now()->toDateTimeString()
        );

        $response = $this->get($this->historyUrl($team->id, $federation->id), $this->header);
        $content = $response->decodeResponseJson();

        $this->assertSame('failed', $content['data'][0]['status']);
        $this->assertStringContainsString('must NOT have additional properties', $content['data'][0]['message']);
    }

    public function test_only_the_latest_attempt_per_dataset_counts(): void
    {
        [$team, $federation] = $this->makeFederation();

        $this->makeRun($team, $federation, 'uuid-retry', 'pid-retry', 0, 'first attempt failed', now()->subMinute()->toDateTimeString());
        $this->makeRun($team, $federation, 'uuid-retry', 'pid-retry', 1, 'CREATED', now()->toDateTimeString());

        $response = $this->get($this->historyUrl($team->id, $federation->id), $this->header);
        $content = $response->decodeResponseJson();

        $this->assertSame('success', $content['data'][0]['status']);
        $this->assertNull($content['data'][0]['message']);
    }

    public function test_a_federation_not_belonging_to_the_requested_team_is_rejected(): void
    {
        [, $federation] = $this->makeFederation();
        $otherTeam = Team::factory()->create();

        $response = $this->get($this->historyUrl($otherTeam->id, $federation->id), $this->header);

        $response->assertStatus(400);
    }

    public function test_a_non_numeric_per_page_is_rejected_with_a_validation_error(): void
    {
        [$team, $federation] = $this->makeFederation();

        $response = $this->get($this->historyUrl($team->id, $federation->id) . '?per_page=abc', $this->header);

        $response->assertStatus(400);
    }
}
