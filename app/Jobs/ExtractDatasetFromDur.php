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
        $nonGatewayDatasets = $dur['non_gateway_datasets'];
        $unmatched = array();
        foreach ($nonGatewayDatasets as $d) {
            // Try to match on url
            if (str_contains($d, env('GATEWAY_URL'))) {
                $exploded = explode('/', $d);
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

            // Try to string match on dataset titles
            // BES 30/10/24: skip this attempt if running on an sqlite DB_CONNECTION
            // because JSON_UNQUOTE does not exist in sqlite
            // and the alternative of grabbing and searching all the metadata is computationally infeasible
            if (env('DB_CONNECTION') !== 'sqlite') {
                $dCleaned = trim($d);
                $datasetVersion = DatasetVersion::whereRaw(
                    "LOWER(JSON_EXTRACT(JSON_UNQUOTE(metadata), '$.metadata.summary.shortTitle')) LIKE LOWER(?)",
                    ["%$dCleaned%"]
                )->latest('version')->first();
                if ($datasetVersion) {
                    DurHasDatasetVersion::create([
                        'dur_id' => $durId,
                        'dataset_version_id' => $datasetVersion->id
                    ]);
                    continue;
                }
            }

            // If no match above, assume $d is a non gateway dataset
            $unmatched[] = $d;
        }

        $dur->update([
            'non_gateway_datasets' => $unmatched
        ]);
    }
}
