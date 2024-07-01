<?php

namespace App\Jobs;

use Exception;
use MetadataManagementController AS MMC;

use App\Models\Dataset;
use App\Models\NamedEntities;
use App\Models\DatasetHasNamedEntities;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Http;

class TermExtraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 300;
    
    private string $datasetId = '';
    private string $data = '';

    private bool $reIndexElastic = false;

    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, string $data, ?string $elasticIndex = "on")
    {
        $this->datasetId = $datasetId;
        $this->data = $data;

        $this->reIndexElastic = ($elasticIndex === "on" ? true : false);
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
        if($tedEnabled === true){
            $this->postToTermExtractionDirector(json_encode($data['metadata']), $this->datasetId);
        }

        if ($this->reIndexElastic) {
            MMC::reindexElastic($this->datasetId);
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

            if ($response->json() && array_key_exists('extracted_terms', $response->json())) {
                foreach ($response->json()['extracted_terms'] as $n) {
                    $named_entities = NamedEntities::create([
                        'name' => $n,
                    ]);
                    DatasetHasNamedEntities::updateOrCreate([
                        'dataset_id' => $datasetId,
                        'named_entities_id' => $named_entities->id
                    ]);
                }
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}