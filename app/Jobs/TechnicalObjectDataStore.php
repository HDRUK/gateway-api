<?php

namespace App\Jobs;

use Mauro;
use Exception;
use MetadataManagementController AS MMC;

use App\Models\Dataset;
use App\Models\NamedEntities;
use App\Models\DatasetHasNamedEntities;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Http;

class TechnicalObjectDataStore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $datasetId = '';
    private string $data = '';


    /**
     * Create a new job instance.
     */
    public function __construct(
        string $datasetId,
        string $data
    )
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
        $data = json_decode(gzdecode(gzuncompress(base64_decode($this->data))), true);

        foreach ($data['structuralMetadata'] as $class) {
            $mauroResponse = Mauro::createDataClass($this->datasetId, $class['name'], $class['description']);
            foreach ($class['columns'] as $element) {
                $mauro = Mauro::createDataElement($this->datasetId, $mauroResponse['id'],
                    $element['name'], $element['description'], $element['dataType']);
            }
        }

        $this->postToTermExtractionDirector(json_encode($data));

        MMC::reindexElastic($data, $this->datasetId);

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
        $response = Http::withBody(
            $dataset, 'application/json'
        )->post(env('TED_SERVICE_URL'));

        try {
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

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }
}
