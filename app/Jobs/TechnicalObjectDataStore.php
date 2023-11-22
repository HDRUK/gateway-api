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
        if (!$this->update) {
            $this->deleteAllDataClasses();

            //structuralMetadata might not be defined
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
        }
        else {
            // Here we handle the case where it's an update rather than create. Need to inspect the current metadata,
            // and compare to the incoming, then make only the changes required.

            // structuralMetadata might not be defined
            if(!isset($data['structuralMetadata']) || empty($data['structuralMetadata'])){
                $this->deleteAllDataClasses();
                return;
            }
            // otherwise, compare all dataclasses from request and from Mauro.
            //
            // (1) Those which are present in both (by comparing request['name'] == Mauro['label']) 
            //     need their contents comparing and updating if not the same
            // (2) Those not present in Mauro need creating, along withtheir child dataElements.
            // (3) Those which are not present in the request need to be deleted.
            // In (1), then we need to compare all data elements in the same way.

            $mauroDataClassesResponse = Mauro::getAllDataClasses($this->datasetId);

            /*
            * Calculate the 3 categories of DataClasses to be handled
            */
            $classesToDelete = array_diff(array_column($mauroDataClassesResponse['items'], 'label'), 
                                          array_column($data['structuralMetadata'], 'name'));

            $classesToAdd = array_diff(array_column($data['structuralMetadata'], 'name'), 
                                       array_column($mauroDataClassesResponse['items'], 'label'));
            
            // These dataClasses are present in both, so we need to apply any updates required 
            // - assume Mauro will handle this efficiently if we just ask for each to be updated 
            //   without ourselves doing a comparison.
            $classesToCheck = array_intersect(array_column($data['structuralMetadata'], 'name'), 
                                              array_column($mauroDataClassesResponse['items'], 'label'));

            /*
            * Handle the 3 categories appropriately
            */

            /*
            * Handle the "delete" DataClasses which are not present in the incoming request but are in the existing Mauro DataModel.
            */
            foreach ($classesToDelete as $className){
                $classesToDeleteContent = $this->getByLabel($className, 
                                                            $mauroDataClassesResponse['items']);
                if ($classesToDeleteContent) {
                    $mauroDeleteResponse = Mauro::deleteDataClass($classesToDeleteContent['id'], 
                                                                  $this->datasetId);
                }
            }

            /*
            * Handle the "add" DataClasses which are present in the incoming request but not in the existing Mauro DataModel.
            */
            foreach ($classesToAdd as $className){
                // Find the correct entry from the incoming request
                $classesToAddContent = $this->getByName($className, 
                                                        $data['structuralMetadata']);
                
                // Create the Mauro DataClass, and its child DataElements
                if ($classesToAddContent) {
                    $mauroCreateResponse = Mauro::createDataClass($this->datasetId, 
                                                                  $classesToAddContent['name'], 
                                                                  $classesToAddContent['description']);

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

            /*
            * Handle the "check" DataClasses which have names present in the existing Mauro DataModel and the incoming request.
            */
            foreach ($classesToCheck as $className){
                // Get the appropriate entry from the incoming request
                $classesToCheckContent = $this->getByName($className, 
                                                          $data['structuralMetadata']);

                // Get the appropriate entry from the Mauro list
                $classToCheckContentMauro = $this->getByLabel($className, 
                                                              $mauroDataClassesResponse['items']);
                
                if ($classesToCheckContent) {
                    // Update the DataClass in Mauro - in case the details have been modified
                    $mauroUpdateResponse = Mauro::updateDataClass($this->datasetId, 
                                                                  $classesToCheckContent['name'], 
                                                                  $classesToCheckContent['description'], 
                                                                  $classToCheckContentMauro['id']);
                    
                    $dataElementsContentMauro = Mauro::getAllDataElements($this->datasetId, 
                                                                          $classToCheckContentMauro['id'])['items'];
                    // Now that the dataClass has been updated, we need to check all child DataElements 
                    // against the request, to see which need deletion/creation/update.

                    /*
                    * Calculate the 3 categories of DataElements to be handled
                    */
                    $elementsToAdd = array_diff(array_column($classesToCheckContent['columns'], 'name'), 
                                                array_column($dataElementsContentMauro, 'label'));

                    $elementsToDelete = array_diff(array_column($dataElementsContentMauro, 'label'), 
                                                   array_column($classesToCheckContent['columns'], 'name'));

                    $elementsToCheck = array_intersect(array_column($dataElementsContentMauro, 'label'), 
                                                       array_column($classesToCheckContent['columns'], 'name'));

                    // Handle the "add" DataElements which are present in the incoming request but not in the existing Mauro DataClass.
                    foreach ($elementsToAdd as $elementName){

                        // Find the correct entry from the incoming request
                        $elementToAddContent = $this->getByName($elementName, 
                                                                $this->getByName($className, 
                                                                                 $data['structuralMetadata'])['columns']
                                                                );
                                                            
                        if ($elementToAddContent) {
                            $mauroCreateElementResponse = Mauro::createDataElement(
                                $this->datasetId, 
                                $classToCheckContentMauro['id'], 
                                $elementToAddContent['name'],
                                $elementToAddContent['description'],
                                $elementToAddContent['dataType']
                            );
                        }
                    }

                    // Handle the "check" DataElements which are present in both the incoming request and the existing Mauro DataClass.
                    foreach ($elementsToCheck as $elementName){
                        $elementToCheckContent = $this->getByName($elementName, 
                                                                  $this->getByName($className, 
                                                                                   $data['structuralMetadata'])['columns']
                                                                  );
                        $elementToCheckContentMauro = $this->getByLabel($elementName, 
                                                                        $dataElementsContentMauro);

                        if ($elementToCheckContent) {
                            $mauroUpdateResponse = Mauro::updateDataElement($this->datasetId, 
                                                                            $elementToCheckContent['name'], 
                                                                            $elementToCheckContent['description'], 
                                                                            $classToCheckContentMauro['id'], 
                                                                            $elementToCheckContentMauro['id']);
                         }

                         $allDataElements = Mauro::getAllDataElements($this->datasetId, 
                                                                      $classToCheckContentMauro['id']);
                    }

                    foreach ($elementsToDelete as $elementName){
                        $elementToDeleteContent = $this->getByLabel($elementName, 
                                                                    $dataElementsContentMauro);

                        if ($elementToDeleteContent) {
                            $mauroDeleteResponse = Mauro::deleteDataElement($elementToDeleteContent['id'],
                                                                            $this->datasetId, 
                                                                            $classToCheckContentMauro['id']);
                        }
                    }
                }
            }
        }

        var_dump('hierarchy:');
        $hierarchyResponse = Mauro::getDataModelHierarchy($this->datasetId);
        var_dump($hierarchyResponse);
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
    * Returns whether the supplied $array contains the supplied $name in the element 'name'
    */
    private function containsName(string $name, array $array) {
        if (!array_key_exists('name', $array)) {
            return false;
        }
        return $array['name'] == $name;
    }

    private function getByName(string $className, array $array) {
        // Get the appropriate entry from the Mauro list
        foreach ($array as $value) {
            if ($this->containsName($className, $value)) {
                return $value;
            }
        }
        return null;
    }

     /*
    * Returns whether the supplied $array contains the supplied $name in the element 'name'
    */
    private function containsLabel(string $label, array $array) {
        if (!array_key_exists('label', $array)) {
            return false;
        }
        return $array['label'] == $label;
    }

    private function getByLabel(string $className, array $array) {
        // Get the appropriate entry from the Mauro list
        foreach ($array as $value) {
            if ($this->containsLabel($className, $value)) {
                return $value;
            }
        }
        return null;
    }

}
