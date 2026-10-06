<?php

namespace Tests\Feature;

use App\Events\FederationProcessed;
use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class ReconcileStuckFederationsTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
        Queue::fake();
    }

    private function makeRunningFederation(?string $batchId): Federation
    {
        $team = Team::factory()->create();
        $federation = Federation::factory()->create([
            'enabled' => true,
            'tested' => true,
            'is_running' => true,
            'current_batch_id' => $batchId,
            'error' => false,
            'error_text' => null,
        ]);
        TeamHasFederation::create(['team_id' => $team->id, 'federation_id' => $federation->id]);

        return $federation;
    }

    private function seedBatch(string $id, string $name, int $total, int $pending, int $failed, ?int $finishedAt = null): void
    {
        DB::table('job_batches')->insert([
            'id' => $id,
            'name' => $name,
            'total_jobs' => $total,
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'failed_job_ids' => '[]',
            'options' => serialize([]),
            'cancelled_at' => null,
            'created_at' => now()->subMinutes(10)->timestamp,
            'finished_at' => $finishedAt,
        ]);
    }

    private function runIngestion(): void
    {
        $this->artisan('app:gateway-metadata-ingestion')->assertSuccessful();
    }

    public function test_a_running_federation_whose_batch_has_failures_and_no_jobs_left_is_finalised_as_errored(): void
    {
        $federation = $this->makeRunningFederation('stuck-batch');
        // 102 succeeded, 5 failed: nothing left to run.
        $this->seedBatch('stuck-batch', "federation-{$federation->id}-uuid-stuck", total: 107, pending: 5, failed: 5);

        $this->runIngestion();

        $fresh = $federation->fresh();
        $this->assertFalse($fresh->is_running);
        $this->assertTrue($fresh->error);
        $this->assertStringContainsString('uuid-stuck', $fresh->error_text);
    }

    public function test_a_running_federation_whose_batch_over_counted_a_failure_is_finalised(): void
    {
        $federation = $this->makeRunningFederation('over-counted-batch');
        $this->seedBatch('over-counted-batch', "federation-{$federation->id}-uuid-over", total: 2, pending: 0, failed: 1);

        $this->runIngestion();

        $fresh = $federation->fresh();
        $this->assertFalse($fresh->is_running);
        $this->assertTrue($fresh->error);
    }

    public function test_a_running_federation_whose_successful_batch_was_never_finalised_is_finalised_as_a_success(): void
    {
        Event::fake([FederationProcessed::class]);
        $federation = $this->makeRunningFederation('done-batch');
        $this->seedBatch('done-batch', "federation-{$federation->id}-uuid-done", total: 3, pending: 0, failed: 0, finishedAt: now()->timestamp);

        $this->runIngestion();

        Event::assertDispatchedTimes(FederationProcessed::class, 1);
        $this->assertFalse($federation->fresh()->is_running);
    }

    public function test_a_federation_with_jobs_left_to_run_is_left_running(): void
    {
        $federation = $this->makeRunningFederation('live-batch');
        $this->seedBatch('live-batch', "federation-{$federation->id}-uuid-live", total: 10, pending: 4, failed: 1);

        $this->runIngestion();

        $this->assertTrue($federation->fresh()->is_running);
    }

    public function test_a_federation_that_has_claimed_but_not_dispatched_its_batch_is_left_running(): void
    {
        $federation = $this->makeRunningFederation(null);

        $this->runIngestion();

        $this->assertTrue($federation->fresh()->is_running);
    }
}
