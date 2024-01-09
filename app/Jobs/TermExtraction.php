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

    private string $datasetId = '';
    private array $data = array();

    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, array $data)
    {
        $this->datasetId = $datasetId;
        $this->data = $data;
    }

    /**
     * Execute the job.
     * 
     * @return void
     */
    public function handle(): void
    {
        $datasetModel = Dataset::where('datasetid', $this->datasetId)->first();

        $tedUrl = env('TED_SERVICE_URL');
        $tedEnabled = env('TED_ENABLED');

        if (!empty($tedUrl) && $tedEnabled) {
            $this->postToTermExtractionDirector(json_encode($this->data));
        }

        MMC::reindexElastic($this->data, $this->datasetId);

        // Jobs aren't garbage collected, so free up
        // resources used before tear down
        unset($this->datasetId);
        unset($this->data);
    }

    /**
     * Passes the incoming dataset to TED for extraction
     * 
     * @param string $dataset   The dataset json passed to this process
     * 
     * @return void
     */
    private function postToTermExtractionDirector(string $dataset): void
    {
        try {
            $response = Http::withBody(
                $dataset,
                'application/json'
            )->post(env('TED_SERVICE_URL'));

            if (array_key_exists('extracted_terms', $response->json())) {
                foreach ($response->json()['extracted_terms'] as $n) {
                    $named_entities = NamedEntities::create([
                        'name' => $n,
                    ]);
                    $datasetPrimary = Dataset::where('datasetid', $this->datasetId)
                        ->first()
                        ->id;
                    DatasetHasNamedEntities::updateOrCreate([
                        'dataset_id' => $datasetPrimary,
                        'named_entities_id' => $named_entities->id
                    ]);
                }
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}