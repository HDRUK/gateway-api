<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\NightlyDatasetTest;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Horizon\Contracts\Silenced;

class NightlyDatasetTestJob implements ShouldQueue, Silenced
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;

    public function handle(): void
    {
        // We run a single rolling window of results here, every night. Will only have
        // as many rows as we have ACTIVE datasets.
        NightlyDatasetTest::truncate();

        $concurrency = (int) config('gateway.nightly_dataset_test_concurrency');

        Dataset::where('status', Dataset::STATUS_ACTIVE)
            ->select('id')
            ->chunkById($concurrency, function ($datasets) {
                $ids = $datasets->pluck('id');

                $responses = Http::pool(fn (Pool $pool) => $ids->map(
                    fn ($id) => $pool->as($id)
                        ->timeout(30)
                        ->withOptions(['stream' => true])
                        ->get($this->datasetUrl($id))
                ));

                foreach ($ids as $id) {
                    $response = $responses[$id];

                    NightlyDatasetTest::create([
                        'dataset_id' => $id,
                        'status_code' => $response instanceof Response ? $response->status() : null,
                    ]);
                }
            });
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
