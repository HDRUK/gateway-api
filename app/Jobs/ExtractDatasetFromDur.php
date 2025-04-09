<?php

namespace App\Jobs;

use App\Models\Dur;
use App\Models\Dataset;
use Illuminate\Bus\Queueable;
use App\Models\DatasetVersion;
use App\Models\DurHasDatasetVersion;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Validator;

class ExtractDatasetFromDur implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $durId = 0;

    /**
     * Create a new job instance.
     */
    public function __construct(int $durId)
    {
        $this->durId = $durId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->durId) {
            return;
        }

        $this->linkDatasets($this->durId);
    }

    private function linkDatasets(int $durId): void
    {
        $dur = Dur::findOrFail($durId);
        $nonGatewayDatasets = array_filter(array_map('trim', $dur['non_gateway_datasets'])) ?? [];
        $unmatched = array();
        foreach ($nonGatewayDatasets as $nonGatewayDataset) {
            $nonDataset = trim($nonGatewayDataset);

            // Try to match on url
            $isUrl = Validator::make([
                'url' => $nonDataset
            ], [
                'url' => 'required|url|starts_with:' . env('GATEWAY_URL'),
            ]);

            if (!$isUrl->fails()) {
                $exploded = explode('/', $nonDataset);
                $datasetId = (int) end($exploded);
                $dataset = Dataset::where('id', $datasetId)->first();
                if ($dataset) {
                    $dvID = $dataset->latestVersionID($datasetId);
                    DurHasDatasetVersion::create([
                        'dur_id' => $durId,
                        'dataset_version_id' => $dvID
                    ]);
                    continue;
                }
            }

            $datasetVersion = DatasetVersion::whereRaw(
                'LOWER(short_title) LIKE ?',
                ['%' . strtolower($nonDataset) . '%']
            )->latest('version')->first();
            if ($datasetVersion) {
                DurHasDatasetVersion::create([
                    'dur_id' => $durId,
                    'dataset_version_id' => $datasetVersion->id
                ]);
                continue;
            }

            // If no match above, assume $d is a non gateway dataset
            $unmatched[] = $nonDataset;
        }

        $dur->update([
            'non_gateway_datasets' => $unmatched
        ]);
    }
}
