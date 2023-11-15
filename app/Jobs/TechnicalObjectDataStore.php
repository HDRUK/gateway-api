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
    public function __construct(string $datasetId, string $data)
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

        $this->deleteAllDataClasses();

        //structuralMetadat might not be defined
        if(!isset($data['structuralMetadata']) || empty($data['structuralMetadata'])){
            return;
        }

        foreach ($data['structuralMetadata'] as $class) {
            $mauroCreateResponse = Mauro::createDataClass($this->datasetId, $class['name'], $class['description']);
            foreach ($class['columns'] as $element) {
                Mauro::createDataElement(
                    $this->datasetId,
                    $mauroCreateResponse['id'],
                    $element['name'],
                    $element['description'],
                    $element['dataType']
                );
            }
        }

        $tedUrl = env('TED_SERVICE_URL');
        $tedEnabled = env('TED_ENABLED');

        if (!empty($tedUrl) && $tedEnabled) {
            $this->postToTermExtractionDirector(json_encode($data));
        }

        MMC::reindexElastic($data, $this->datasetId);

        // Jobs aren't garbage collected, so free up
        // resources used before tear down
        unset($this->datasetId);
        unset($this->data);
    }

    /**
     * Delete all data classes assigned to this dataset
     * 
     * @return void
     */
    public function deleteAllDataClasses(): void
    {
        try {
            $currDataClasses = Mauro::getAllDataClasses($this->datasetId);
            if (array_key_exists('items', $currDataClasses)) {
                foreach ($currDataClasses['items'] as $element) {
                    Mauro::deleteDataClass($element['id'], $this->datasetId);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
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
