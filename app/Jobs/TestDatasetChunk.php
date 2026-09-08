<?php

namespace App\Jobs;

use App\Models\NightlyDatasetTest;
use Http;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Horizon\Contracts\Silenced;

/**
 * Checks reachability for one chunk of dataset ids. Dispatched as a batch of
 * these, one per chunk, from NightlyDatasetTestJob instead of looping over
 * every chunk inside a single job — keeps each job's runtime bounded
 * regardless of how many active datasets exist system-wide.
 */
class TestDatasetChunk implements ShouldQueue, Silenced
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public $timeout = 90;

    public function __construct(private readonly array $datasetIds)
    {
        $this->onQueue('indexing');
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $responses = Http::pool(fn (Pool $pool) => collect($this->datasetIds)->map(
            fn ($id) => $pool->as($id)
                ->timeout(30)
                ->get($this->datasetUrl($id))
        ));

        foreach ($this->datasetIds as $id) {
            $response = $responses[$id];

            NightlyDatasetTest::create([
                'dataset_id' => $id,
                'status_code' => $response instanceof Response ? $response->status() : null,
            ]);
        }
    }

    private function datasetUrl(int $datasetId): string
    {
        return rtrim(config('gateway.gateway_url'), '/') . '/en/dataset/' . $datasetId;
    }

    public function tags(): array
    {
        return ['nightly_dataset_tests'];
    }
}
