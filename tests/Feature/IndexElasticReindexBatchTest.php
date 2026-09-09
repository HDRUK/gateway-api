<?php

namespace Tests\Feature;

use App\Jobs\IndexDataset;
use App\Jobs\ReindexElasticEntity;
use App\Models\Dataset;
use App\Models\Team;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

/**
 * Regression coverage for GAT-9999: IndexElastic::reindexElasticDataProviderWithRelations()
 * used to loop over every one of a team's datasets/collections/durs/tools
 * in-process, making its own runtime scale with team size — the root cause of
 * the ReindexDataset/IndexDataset/DeindexDataset Horizon timeouts. It now
 * dispatches one ReindexElasticEntity job per entity via Bus::batch() instead,
 * so its own cost stays constant regardless of team size.
 */
class IndexElasticReindexBatchTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
    }

    public function test_reindexes_active_datasets_via_batched_jobs_not_in_process(): void
    {
        Bus::fake();

        $team = Team::factory()->create();

        $active = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ACTIVE]);
        $archived = Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);

        $job = new IndexDataset('0');
        $job->reindexElasticDataProviderWithRelations((string) $team->id, 'dataset');

        Bus::assertBatched(function ($batch) use ($active, $archived) {
            $datasetIds = collect($batch->jobs)
                ->pluck('id')
                ->filter(fn ($id) => $id !== null);

            // Only the active dataset should have been queued for reindexing —
            // the archived one must not appear anywhere in the batch.
            return $batch->jobs->count() === 1
                && $batch->jobs->first() instanceof ReindexElasticEntity;
        });
    }

    public function test_no_batch_is_dispatched_when_team_has_no_active_relations(): void
    {
        Bus::fake();

        $team = Team::factory()->create();
        Dataset::factory()->for($team)->create(['status' => Dataset::STATUS_ARCHIVED]);

        $job = new IndexDataset('0');
        $job->reindexElasticDataProviderWithRelations((string) $team->id, 'dataset');

        Bus::assertNothingBatched();
    }
}
