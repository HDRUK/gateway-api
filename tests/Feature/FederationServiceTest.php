<?php

namespace Tests\Feature;

use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use App\Services\FederationService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederationServiceTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_update_clears_a_previously_recorded_error(): void
    {
        $team = Team::factory()->create();

        $federation = Federation::factory()->create([
            'error' => true,
            'error_text' => 'a previous connection failure',
        ]);

        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);

        (new FederationService())->update($team->id, $federation->id, [
            'federation_type' => $federation->federation_type,
            'auth_type' => 'NO_AUTH',
            'endpoint_baseurl' => $federation->endpoint_baseurl,
            'endpoint_datasets' => $federation->endpoint_datasets,
            'endpoint_dataset' => $federation->endpoint_dataset,
            'run_time_hour' => $federation->run_time_hour,
            'run_time_minute' => '00',
            'enabled' => true,
            'notifications' => [],
        ]);

        $fresh = $federation->fresh();

        $this->assertFalse($fresh->error);
        $this->assertNull($fresh->error_text);
    }

    public function test_clear_error_for_team_clears_a_previously_recorded_error(): void
    {
        $team = Team::factory()->create();

        $federation = Federation::factory()->create([
            'error' => true,
            'error_text' => 'a previous connection failure',
        ]);

        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);

        (new FederationService())->clearErrorForTeam($team->id, $federation->id);

        $fresh = $federation->fresh();

        $this->assertFalse($fresh->error);
        $this->assertNull($fresh->error_text);
    }

    public function test_clear_error_for_team_does_not_affect_a_different_teams_federation(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        $federation = Federation::factory()->create([
            'error' => true,
            'error_text' => 'a previous connection failure',
        ]);

        TeamHasFederation::create([
            'team_id' => $team->id,
            'federation_id' => $federation->id,
        ]);

        (new FederationService())->clearErrorForTeam($otherTeam->id, $federation->id);

        $fresh = $federation->fresh();

        $this->assertTrue($fresh->error);
        $this->assertSame('a previous connection failure', $fresh->error_text);
    }

    private function makeFederationWithBatch(bool $isRunning, int $pending): array
    {
        $team = Team::factory()->create();
        $federation = Federation::factory()->create([
            'enabled' => true,
            'tested' => true,
            'is_running' => $isRunning,
            'current_batch_id' => 'current-batch',
        ]);
        TeamHasFederation::create(['team_id' => $team->id, 'federation_id' => $federation->id]);

        DB::table('job_batches')->insert([
            'id' => 'current-batch',
            'name' => "federation-{$federation->id}-uuid-current",
            'total_jobs' => 10,
            'pending_jobs' => $pending,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'options' => serialize([]),
            'cancelled_at' => null,
            'created_at' => now()->subMinutes(5)->timestamp,
            'finished_at' => $pending === 0 ? now()->timestamp : null,
        ]);

        return [$team, $federation];
    }

    private function saveWithEnabled(Team $team, Federation $federation, bool $enabled): void
    {
        (new FederationService())->update($team->id, $federation->id, [
            'federation_type' => $federation->federation_type,
            'auth_type' => 'NO_AUTH',
            'endpoint_baseurl' => $federation->endpoint_baseurl,
            'endpoint_datasets' => $federation->endpoint_datasets,
            'endpoint_dataset' => $federation->endpoint_dataset,
            'run_time_hour' => $federation->run_time_hour,
            'run_time_minute' => '00',
            'enabled' => $enabled,
            'notifications' => [],
        ]);
    }

    public function test_disabling_a_running_federation_stops_its_sync(): void
    {
        [$team, $federation] = $this->makeFederationWithBatch(isRunning: true, pending: 6);

        $this->saveWithEnabled($team, $federation, false);

        $this->assertTrue(Bus::findBatch('current-batch')->cancelled());
    }

    public function test_disabling_a_federation_that_is_not_running_leaves_its_last_run_alone(): void
    {
        [$team, $federation] = $this->makeFederationWithBatch(isRunning: false, pending: 0);

        $this->saveWithEnabled($team, $federation, false);

        $this->assertFalse(Bus::findBatch('current-batch')->cancelled());
    }

    public function test_saving_a_running_federation_that_stays_enabled_does_not_stop_its_sync(): void
    {
        [$team, $federation] = $this->makeFederationWithBatch(isRunning: true, pending: 6);

        $this->saveWithEnabled($team, $federation, true);

        $this->assertFalse(Bus::findBatch('current-batch')->cancelled());
    }
}
