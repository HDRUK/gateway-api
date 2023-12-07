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
    private bool $update = false;


    /**
     * Create a new job instance.
     */
    public function __construct(string $datasetId, string $data, bool $update)
    {
        $this->datasetId = $datasetId;
        $this->data = $data;
        $this->update = $update;
    }

    /**
     * Execute the job.
     * 
     * @return void
     */
    public function handle(): void
    {
        $data = json_decode(gzdecode(gzuncompress(base64_decode($this->data))), true);
        $datasetModel = Dataset::where('datasetid', $this->datasetId)->first();
        if (!$this->update) {
            $this->deleteAllDataClasses();

            //structuralMetadata might not be defined
            if(!isset($data['structuralMetadata']) || empty($data['structuralMetadata'])){
                $datasetModel->has_technical_details = 0;
                $datasetModel->save();
            } else {
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
            }
        }
        else {
            // Here we handle the case where it's an update rather than create. 
            // Update inspects the current metadata, compares to the incoming request,
            // then make only the changes required.

            // structuralMetadata might not be defined
            if(!isset($data['structuralMetadata']) || empty($data['structuralMetadata'])){
                $datasetModel->has_technical_details = 0;
                $datasetModel->save();
                $this->deleteAllDataClasses();
            } else {

                // Compare all DataClasses from request and from Mauro.
                //
                // (1) Those which are present in both need their contents comparing and updating if not the 
                //     same. All child DataElements require checking in the same way.
                // (2) Those not present in Mauro need creating, along with their child DataElements.
                // (3) Those which are not present in the request need to be deleted.

                $mauroDataClassesResponse = Mauro::getAllDataClasses($this->datasetId);
                $mauroDataClasses = $mauroDataClassesResponse['items'];

                /*
                * Calculate the 3 categories of DataClasses
                */
                $classesToDelete = array_diff(
                    array_column($mauroDataClasses, 'label'), 
                    array_column($data['structuralMetadata'], 'name')
                );

                $classesToAdd = array_diff(
                    array_column($data['structuralMetadata'], 'name'), 
                    array_column($mauroDataClasses, 'label')
                );
                
                $classesToCheck = array_intersect(
                    array_column($data['structuralMetadata'], 'name'), 
                    array_column($mauroDataClasses, 'label')
                );

                /*
                * Handle the 3 categories appropriately
                */

                foreach ($classesToDelete as $className){
                    $classesToDeleteContent = $this->getBy($className, $mauroDataClasses, 'label');
                    if ($classesToDeleteContent) {
                        $mauroDeleteResponse = Mauro::deleteDataClass(
                            $classesToDeleteContent['id'], 
                            $this->datasetId
                        );
                    }
                }

                foreach ($classesToAdd as $className){
                    // Find the correct entry from the incoming request
                    $classesToAddContent = $this->getBy($className, $data['structuralMetadata'], 'name');
                    
                    // Create the Mauro DataClass, and its child DataElements
                    if ($classesToAddContent) {
                        $mauroCreateResponse = Mauro::createDataClass(
                            $this->datasetId, 
                            $classesToAddContent['name'], 
                            $classesToAddContent['description']
                        );

                        foreach ($classesToAddContent['columns'] as $element) {
                            Mauro::createDataElement(
                                $this->datasetId,
                                $mauroCreateResponse['id'],
                                $element['name'],
                                $element['description'],
                                $element['dataType']
                            );
                        }
                    }
                }

                foreach ($classesToCheck as $className){
                    // Get the appropriate entry from the incoming request
                    $classToCheckContent = $this->getBy($className, $data['structuralMetadata'], 'name');

                    // Get the appropriate entry from the Mauro list
                    $mauroClassId = $this->getBy($className, $mauroDataClasses, 'label')['id'];
                    
                    if ($classToCheckContent) {
                        // Update the DataClass in Mauro - in case the details have been modified
                        $mauroUpdateResponse = Mauro::updateDataClass(
                            $this->datasetId, 
                            $classToCheckContent['name'], 
                            $classToCheckContent['description'], 
                            $mauroClassId
                        );
                        
                        $mauroDataElements = Mauro::getAllDataElements(
                            $this->datasetId, 
                            $mauroClassId
                        )['items'];

                        /*
                        * Calculate the 3 categories of DataElements to be handled
                        */
                        $elementsToAdd = array_diff(
                            array_column($classToCheckContent['columns'], 'name'), 
                            array_column($mauroDataElements, 'label')
                        );

                        $elementsToDelete = array_diff(
                            array_column($mauroDataElements, 'label'), 
                            array_column($classToCheckContent['columns'], 'name')
                        );

                        $elementsToCheck = array_intersect(
                            array_column($mauroDataElements, 'label'), 
                            array_column($classToCheckContent['columns'], 'name')
                        );

                        foreach ($elementsToAdd as $elementName){
                            // Find the correct entry from the incoming request
                            $elementToAddContent = $this->getBy(
                                $elementName, 
                                $this->getBy($className, $data['structuralMetadata'], 'name')['columns'],
                                'name'
                            );
                                                                
                            if ($elementToAddContent) {
                                $mauroCreateElementResponse = Mauro::createDataElement(
                                    $this->datasetId, 
                                    $mauroClassId, 
                                    $elementToAddContent['name'],
                                    $elementToAddContent['description'],
                                    $elementToAddContent['dataType']
                                );
                            }
                        }

                        // Handle the "check" DataElements which are present in both the incoming request and the existing Mauro DataClass.
                        foreach ($elementsToCheck as $elementName){
                            $elementToCheckContent = $this->getBy(
                                $elementName, 
                                $this->getBy($className, $data['structuralMetadata'], 'name')['columns'],
                                'name'
                            );
                            $mauroElementId = $this->getBy($elementName, $mauroDataElements, 'label')['id'];

                            if ($elementToCheckContent) {
                                $mauroUpdateResponse = Mauro::updateDataElement(
                                    $this->datasetId, 
                                    $elementToCheckContent['name'], 
                                    $elementToCheckContent['description'], 
                                    $mauroClassId, 
                                    $mauroElementId
                                );
                            }

                            $allDataElements = Mauro::getAllDataElements($this->datasetId, $mauroClassId);
                        }

                        // Handle the "delete" DataElements which are present in the existing Mauro DataClass but not in the incoming request.
                        foreach ($elementsToDelete as $elementName){
                            $elementToDeleteContent = $this->getBy($elementName, $mauroDataElements, 'label');

                            if ($elementToDeleteContent) {
                                $mauroDeleteResponse = Mauro::deleteDataElement(
                                    $elementToDeleteContent['id'],
                                    $this->datasetId, 
                                    $mauroClassId
                                );
                            }
                        }
                    }
                }
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

    /*
    * Returns the first element of $array where $array[element][$key] === $className.
    * Returns null if not found.
    */
    private function getBy(string $className, array $array, string $key): ?array {
        // Get the appropriate entry from the Mauro list
        foreach ($array as $value) {
            if ($this->contains($value, $key, $className)) {
                return $value;
            }
        }
        return null;
    }

    /*
    * Returns whether the supplied array satisfies ($array[$key] === $value)
    */
    private function contains(array $array, string $key, string $value): bool {
        return array_key_exists($key, $array) ? ($array[$key] === $value) : false;
    }

}
