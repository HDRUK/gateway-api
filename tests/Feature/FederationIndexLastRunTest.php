<?php

namespace Tests\Feature;

use App\Models\Federation;
use App\Models\FederationJobRun;
use App\Models\Team;
use App\Models\TeamHasFederation;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederationIndexLastRunTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeFederation(Team $team): Federation
    {
        $federation = Federation::factory()->create([
            'enabled' => true,
            'tested' => true,
            'is_running' => false,
        ]);
        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);

        return $federation;
    }

    private function makeRun(Team $team, Federation $federation, string $jobUuid, string $pid, ?int $status, string $createdAt): FederationJobRun
    {
        $run = FederationJobRun::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
            'job_uuid' => $jobUuid,
            'pid' => $pid,
            'status' => $status,
            'details' => ['message' => 'CREATED'],
            'job_attempts' => 1,
        ]);

        FederationJobRun::where('id', $run->id)->update(['created_at' => $createdAt]);

        return $run->fresh();
    }

    private function indexUrl(int $teamId): string
    {
        return "api/v1/teams/{$teamId}/federations";
    }

    private function findFederationInResponse($content, int $federationId): array
    {
        foreach ($content['data'] as $item) {
            if ($item['id'] === $federationId) {
                return $item;
            }
        }

        $this->fail("Federation {$federationId} not found in response data");
    }

    public function test_federation_with_no_runs_has_null_last_run_at(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team);

        $response = $this->get($this->indexUrl($team->id), $this->header);
        $content = $response->decodeResponseJson();

        $item = $this->findFederationInResponse($content, $federation->id);
        $this->assertNull($item['last_run_at']);
    }

    public function test_federation_with_a_single_run_reports_its_timestamp(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team);

        $createdAt = now()->subHour()->toDateTimeString();
        $this->makeRun($team, $federation, 'uuid-1', 'pid-1', 1, $createdAt);

        $response = $this->get($this->indexUrl($team->id), $this->header);
        $content = $response->decodeResponseJson();

        $item = $this->findFederationInResponse($content, $federation->id);
        $this->assertSame($createdAt, $item['last_run_at']);
    }

    public function test_last_run_at_reflects_the_most_recent_execution_only(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team);

        $older = now()->subDays(2)->toDateTimeString();
        $newer = now()->subDay()->toDateTimeString();

        $this->makeRun($team, $federation, 'uuid-older', 'pid-1', 1, $older);
        $this->makeRun($team, $federation, 'uuid-newer', 'pid-2', 1, $newer);

        $response = $this->get($this->indexUrl($team->id), $this->header);
        $content = $response->decodeResponseJson();

        $item = $this->findFederationInResponse($content, $federation->id);
        $this->assertSame($newer, $item['last_run_at']);
    }

    public function test_last_run_at_is_correctly_attributed_per_federation(): void
    {
        $team = Team::factory()->create();
        $federationA = $this->makeFederation($team);
        $federationB = $this->makeFederation($team);

        $aRunAt = now()->subDays(3)->toDateTimeString();
        $bRunAt = now()->subHour()->toDateTimeString();

        $this->makeRun($team, $federationA, 'uuid-a', 'pid-a', 1, $aRunAt);
        $this->makeRun($team, $federationB, 'uuid-b', 'pid-b', 1, $bRunAt);

        $response = $this->get($this->indexUrl($team->id), $this->header);
        $content = $response->decodeResponseJson();

        $itemA = $this->findFederationInResponse($content, $federationA->id);
        $itemB = $this->findFederationInResponse($content, $federationB->id);

        $this->assertSame($aRunAt, $itemA['last_run_at']);
        $this->assertSame($bRunAt, $itemB['last_run_at']);
    }

    public function test_a_team_with_no_federations_returns_an_empty_list(): void
    {
        $team = Team::factory()->create();

        $response = $this->get($this->indexUrl($team->id), $this->header);

        $response->assertStatus(200);
        $content = $response->decodeResponseJson();
        $this->assertSame([], $content['data']);
    }
}
