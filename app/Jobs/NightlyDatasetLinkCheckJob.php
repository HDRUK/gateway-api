<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\DatasetLinkCheckResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Laravel\Horizon\Contracts\Silenced;

/**
 * Dispatcher: builds chunks of active dataset ids and hands each one off to a
 * CheckDatasetLinksChunk job via Bus::batch(), rather than running every
 * chunk's HTTP checks in-process (see CheckDatasetLinksChunk for why — this
 * job's own runtime used to scale with the total number of active datasets).
 * This job itself now only does cheap DB work, so it stays on the fast/small
 * default tier.
 */
class NightlyDatasetLinkCheckJob implements ShouldQueue, Silenced
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 120;

    public function handle(): void
    {
        // Rolling window of results, rebuilt every run, same convention as NightlyDatasetTestJob.
        DatasetLinkCheckResult::truncate();

        $concurrency = (int) config('gateway.nightly_dataset_link_check_concurrency');

        $jobs = Dataset::where('status', Dataset::STATUS_ACTIVE)
            ->pluck('id')
            ->chunk(max(1, $concurrency))
            ->map(fn ($chunk) => new CheckDatasetLinksChunk($chunk->values()->all()))
            ->all();

        if (empty($jobs)) {
            return;
        }

        Bus::batch($jobs)
            ->name('nightly-dataset-link-check')
            ->onQueue('indexing')
            ->dispatch();
    }

    public function tags(): array
    {
        return ['nightly_dataset_link_check'];
    }
}
