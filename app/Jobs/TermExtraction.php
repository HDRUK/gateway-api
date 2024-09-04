<?php

namespace App\Jobs;

use Auditor;
use Exception;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\NamedEntities;
use App\Models\DatasetVersionHasNamedEntities;
use App\Http\Traits\IndexElastic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class TermExtraction implements ShouldQueue
{
    use IndexElastic;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 1;
    public $timeout = 300;

    private string $datasetId = '';
    private int $version;
    private string $data = '';

    private bool $reIndexElastic = true;

    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, int $version, string $data, ?bool $elasticIndex = true)
    {
        $this->datasetId = $datasetId;
        $this->version = $version;
        $this->data = $data;

        $this->reIndexElastic = is_null($elasticIndex) ? true : $elasticIndex;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $data = json_decode(gzdecode(gzuncompress(base64_decode($this->data))), true);
        $datasetModel = Dataset::where('id', $this->datasetId)->first();

        $tedUrl = env('TED_SERVICE_URL');
        $tedEnabled = env('TED_ENABLED');
        if ($tedEnabled === true) {
            $this->postToTermExtractionDirector(json_encode($data['metadata']), $this->datasetId);
        }

        if ($this->reIndexElastic) {
            $this->reindexElastic($this->datasetId);
        }
    }

    /**
     * Passes the incoming dataset to TED for extraction
     *
     * @param string $dataset   The dataset json passed to this process
     *
     * @return void
     */
    private function postToTermExtractionDirector(string $dataset, string $datasetId): void
    {
        try {
            $response = Http::timeout(300)->withBody(
                $dataset,
                'application/json'
            )->post(env('TED_SERVICE_URL', 'http://localhost:8001'));

            // Fetch the specified dataset version
            $datasetVersion = DatasetVersion::where('dataset_id', $datasetId)
                                ->where('version', $this->version)
                                ->firstOrFail();

            if ($response->successful() && array_key_exists('extracted_terms', $response->json())) {
                foreach ($response->json()['extracted_terms'] as $term) {
                    // Check if the named entity already exists
                    $namedEntity = NamedEntities::create(['name' => $term]);

                    DatasetVersionHasNamedEntities::updateOrCreate([
                        'dataset_version_id' => $datasetVersion->id,
                        'named_entities_id' => $namedEntity->id
                    ]);
                }
            }

        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception($e->getMessage());
        }
    }


}
