<?php

namespace Tests\Feature;

use App\Jobs\ProcessFederation;
use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederationRunNowTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeFederation(Team $team, array $overrides = []): Federation
    {
        $federation = Federation::factory()->create(array_merge([
            'enabled' => true,
            'tested' => true,
            'is_running' => false,
        ], $overrides));

        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);

        return $federation;
    }

    private function runUrl(int $teamId, int $federationId): string
    {
        return "api/v1/teams/{$teamId}/federations/{$federationId}/run";
    }

    public function test_run_now_is_rejected_with_409_when_already_running(): void
    {
        Queue::fake();

        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, ['is_running' => true]);

        $response = $this->get($this->runUrl($team->id, $federation->id), $this->header);

        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'Federation is already running an integration synchronisation.',
        ]);
        Queue::assertNotPushed(ProcessFederation::class);
    }

    public function test_run_now_is_rejected_with_404_when_not_enabled(): void
    {
        Queue::fake();

        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, ['enabled' => false]);

        $response = $this->get($this->runUrl($team->id, $federation->id), $this->header);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Federation not found!',
        ]);
        Queue::assertNotPushed(ProcessFederation::class);
    }

    public function test_run_now_dispatches_the_job_when_eligible(): void
    {
        Queue::fake();

        $team = Team::factory()->create();
        $federation = $this->makeFederation($team);

        $response = $this->get($this->runUrl($team->id, $federation->id), $this->header);

        $response->assertStatus(200);
        Queue::assertPushedOn('federation', ProcessFederation::class);
    }
}
