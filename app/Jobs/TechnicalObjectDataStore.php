<?php

namespace App\Jobs;

use Mauro;

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

        foreach ($data['data']['datasetfields']['technicaldetails'] as $class) {
            $mauroResponse = Mauro::createDataClass($this->datasetId, $class['label'], $class['description']);
            foreach ($class['elements'] as $element) {
                $mauro = Mauro::createDataElement($this->datasetId, $mauroResponse['id'], $element['label'], $element['description'], $element['dataType']['label']);
            }
        }

        $this->postToTermExtractionDirector($data);

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
        $response = Http::post(env('TED_SERVICE_URL') . '/datasets', [
            $dataset,        
        ]);

        dd($response);
    }
}
