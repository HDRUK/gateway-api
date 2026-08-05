<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\NightlyDatasetTest;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Horizon\Contracts\Silenced;
use Throwable;

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
        Dataset::where('status', Dataset::STATUS_ACTIVE)
            ->select('id')
            ->chunkById(100, function ($datasets) {
                foreach ($datasets as $dataset) {
                    $statusCode = $this->checkDataset($dataset->id);

                    NightlyDatasetTest::updateOrCreate(
                        ['dataset_id' => $dataset->id],
                        ['status_code' => $statusCode],
                    );
                }
            });
    }

    private function checkDataset(int $datasetId): ?int
    {
        $url = rtrim(config('app.url'), '/') . '/en/datasets/' . $datasetId;

        try {
            $response = Http::timeout(30)->get($url);

            return $response->status();
        } catch (Throwable $e) {
            return null;
        }
    }

    public function tags(): array
    {
        return ['nightly_dataset_tests'];
    }
}
