<?php

namespace Tests\Feature;

use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederationRunProgressTest extends TestCase
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

    private function seedBatch(string $id, int $total, int $pending, int $failed, ?int $finishedAt): void
    {
        DB::table('job_batches')->insert([
            'id' => $id,
            'name' => 'federation-test-batch',
            'total_jobs' => $total,
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'failed_job_ids' => '[]',
            'options' => serialize([]),
            'cancelled_at' => null,
            'created_at' => now()->subMinutes(5)->timestamp,
            'finished_at' => $finishedAt,
        ]);
    }

    private function indexUrl(int $teamId): string
    {
        return "api/v1/teams/{$teamId}/federations";
    }

    private function showUrl(int $teamId, int $federationId): string
    {
        return "api/v1/teams/{$teamId}/federations/{$federationId}";
    }

    private function findFederationInIndex($content, int $federationId): array
    {
        foreach ($content['data'] as $item) {
            if ($item['id'] === $federationId) {
                return $item;
            }
        }

        $this->fail("Federation {$federationId} not found in response data");
    }

    public function test_progress_is_null_when_not_running(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, ['is_running' => false]);

        $indexItem = $this->findFederationInIndex(
            $this->get($this->indexUrl($team->id), $this->header)->decodeResponseJson(),
            $federation->id
        );
        $showItem = $this->get($this->showUrl($team->id, $federation->id), $this->header)->decodeResponseJson()['data'];

        $this->assertNull($indexItem['progress']);
        $this->assertNull($showItem['progress']);
    }

    public function test_progress_is_null_when_running_with_no_batch_dispatched_yet(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, ['is_running' => true, 'current_batch_id' => null]);

        $showItem = $this->get($this->showUrl($team->id, $federation->id), $this->header)->decodeResponseJson()['data'];

        $this->assertNull($showItem['progress']);
    }

    public function test_progress_is_null_when_current_batch_id_points_at_an_already_finished_batch(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, [
            'is_running' => true,
            'current_batch_id' => 'stale-finished-batch',
        ]);
        $this->seedBatch('stale-finished-batch', total: 10, pending: 0, failed: 0, finishedAt: now()->timestamp);

        $showItem = $this->get($this->showUrl($team->id, $federation->id), $this->header)->decodeResponseJson()['data'];

        $this->assertNull($showItem['progress']);
    }

    public function test_progress_is_null_when_the_batch_has_no_jobs_left_to_run(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, [
            'is_running' => true,
            'current_batch_id' => 'previous-run-batch',
        ]);
        // 102 succeeded, 5 failed: Laravel counts failed jobs as pending and never sets finished_at.
        $this->seedBatch('previous-run-batch', total: 107, pending: 5, failed: 5, finishedAt: null);

        $indexItem = $this->findFederationInIndex(
            $this->get($this->indexUrl($team->id), $this->header)->decodeResponseJson(),
            $federation->id
        );
        $showItem = $this->get($this->showUrl($team->id, $federation->id), $this->header)->decodeResponseJson()['data'];

        $this->assertNull($indexItem['progress']);
        $this->assertNull($showItem['progress']);
    }

    public function test_progress_is_null_when_a_job_was_counted_as_both_succeeded_and_failed(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, [
            'is_running' => true,
            'current_batch_id' => 'over-counted-batch',
        ]);
        $this->seedBatch('over-counted-batch', total: 2, pending: 0, failed: 1, finishedAt: null);

        $showItem = $this->get($this->showUrl($team->id, $federation->id), $this->header)->decodeResponseJson()['data'];

        $this->assertNull($showItem['progress']);
    }

    public function test_progress_reflects_a_genuinely_unfinished_batch(): void
    {
        $team = Team::factory()->create();
        $federation = $this->makeFederation($team, [
            'is_running' => true,
            'current_batch_id' => 'live-batch',
        ]);
        $this->seedBatch('live-batch', total: 128, pending: 65, failed: 2, finishedAt: null);

        $indexItem = $this->findFederationInIndex(
            $this->get($this->indexUrl($team->id), $this->header)->decodeResponseJson(),
            $federation->id
        );
        $showItem = $this->get($this->showUrl($team->id, $federation->id), $this->header)->decodeResponseJson()['data'];

        foreach ([$indexItem, $showItem] as $item) {
            $this->assertSame(128, $item['progress']['total']);
            $this->assertSame(63, $item['progress']['processed']);
            $this->assertSame(2, $item['progress']['failed']);
            $this->assertSame(65, $item['progress']['pending']);
            $this->assertNotNull($item['progress']['started_at']);
        }
    }
}
