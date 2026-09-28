<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\NightlyDatasetTest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Laravel\Horizon\Contracts\Silenced;

/**
 * Dispatcher: builds chunks of active dataset ids and hands each one off to a
 * TestDatasetChunk job via Bus::batch(), rather than running every chunk's
 * HTTP checks in-process (see NightlyDatasetLinkCheckJob's sibling refactor
 * for the same reasoning). This job itself now only does cheap DB work, so
 * it stays on the fast/small default tier.
 */
class NightlyDatasetTestJob implements ShouldQueue, Silenced
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 120;

    public function handle(): void
    {
        // We run a single rolling window of results here, every night. Will only have
        // as many rows as we have ACTIVE datasets.
        NightlyDatasetTest::truncate();

        $concurrency = (int) config('gateway.nightly_dataset_test_concurrency');

        $jobs = Dataset::where('status', Dataset::STATUS_ACTIVE)
            ->pluck('id')
            ->chunk(max(1, $concurrency))
            ->map(fn ($chunk) => new TestDatasetChunk($chunk->values()->all()))
            ->all();

        if (empty($jobs)) {
            return;
        }

        Bus::batch($jobs)
            ->name('nightly-dataset-test')
            ->onQueue('indexing')
            ->dispatch();
    }

    public function tags(): array
    {
        return ['nightly_dataset_tests'];
    }
}
