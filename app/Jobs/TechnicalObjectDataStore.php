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
        var_dump("handle TechnicalObjectDataStore start");
        $data = json_decode(gzdecode(gzuncompress(base64_decode($this->data))), true);
        var_dump($data);
        var_dump('update?', $this->update);
        if (!$this->update) {
            $this->deleteAllDataClasses();

            //structuralMetadata might not be defined
            if(!isset($data['structuralMetadata']) || empty($data['structuralMetadata'])){
                return;
            }
            // var_dump("here3");
            foreach ($data['structuralMetadata'] as $class) {
                // var_dump("here4", $class['name'], $class['description']);
                $mauroCreateResponse = Mauro::createDataClass($this->datasetId, $class['name'], $class['description']);
                foreach ($class['columns'] as $element) {
                    Mauro::createDataElement(
                        $this->datasetId,
                        $mauroCreateResponse['id'],
                        $element['name'],
                        $element['description'],
                        $element['dataType']
                    );
                    // var_dump("here4a", $element);
                }
            }
            // var_dump("here5");
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

            var_dump('getAllDataClasses');
            $mauroDataClassesResponse = Mauro::getAllDataClasses($this->datasetId);
            // var_dump('$mauroDataClassesResponse', $mauroDataClassesResponse);
            // $this->deleteAllDataClasses();
            // var_dump($mauroDataClassesResponse['items']);
            // foreach ($mauroDataClassesResponse['items'] as $class) {
            //     // var_dump(["existing SM class", $class['label'], $class['description']]);
            //     $mauroDataElementResponse = Mauro::getAllDataElements($this->datasetId, $class['id']);
            //     // var_dump($mauroDataElementResponse);
            //     foreach ($mauroDataElementResponse['items'] as $element) {
            //         // var_dump(["existing SM element", $element['label'], $element['description'], $element['dataType']['label']]);

            //     }
            // }
            // var_dump('$data[structuralMetadata]', $data['structuralMetadata']);
            // foreach ($data['structuralMetadata'] as $class) {
            //     // var_dump(["incoming SM class", $class['name'], $class['description']]);
            //     // $mauroCreateResponse = Mauro::createDataClass($this->datasetId, $class['name'], $class['description']);
            //     // foreach ($class['columns'] as $element) {
            //         // var_dump(["incoming SM element", $element['name'], $element['description'], $element['dataType']]);
            //         // Mauro::createDataElement(
            //         //     $this->datasetId,
            //         //     $mauroCreateResponse['id'],
            //         //     $element['name'],
            //         //     $element['description'],
            //         //     $element['dataType']
            //         // );
            //         // var_dump("here4a", $element);
            //     // }
            // }

            var_dump(array_column($mauroDataClassesResponse['items'], 'label'));
            var_dump(array_column($data['structuralMetadata'], 'name'));
            // foreach ($mauroDataClassesResponse['items'] as $class) {
            // 
            // }

            /*
            * Calculate the 3 categories of DataClasses to be handled
            */
            var_dump('diff');
            $classesToDelete = array_diff(array_column($mauroDataClassesResponse['items'], 'label'), 
                                          array_column($data['structuralMetadata'], 'name'));
            var_dump('classesToDelete', $classesToDelete);
            // foreach ($classesToDelete as $className){

            // }
            $classesToAdd = array_diff(array_column($data['structuralMetadata'], 'name'), 
                                       array_column($mauroDataClassesResponse['items'], 'label'));
            var_dump('classesToAdd', $classesToAdd);
            

            // These dataClasses are present in both, so we need to apply any updates required 
            // - assume Mauro will handle this efficiently if we just ask for each to be updated 
            //   without ourselves doing a comparison.
            $classesToCheck = array_intersect(array_column($data['structuralMetadata'], 'name'), 
                                              array_column($mauroDataClassesResponse['items'], 'label'));
            var_dump('classesToCheck', $classesToCheck);


            /*
            * Handle the 3 categories appropriately
            */

            /*
            * Handle the "delete" DataClasses which are not present in the incoming request but are in the existing Mauro DataModel.
            */
            foreach ($classesToDelete as $className){
                $classesToDeleteContent = $this->getByLabel($className, $mauroDataClassesResponse['items']);
                if ($classesToDeleteContent) {
                    $mauroDeleteResponse = Mauro::deleteDataClass($classesToDeleteContent['id'], $this->datasetId);
                }
            }

            /*
            * Handle the "add" DataClasses which are present in the incoming request but not in the existing Mauro DataModel.
            */
            var_dump('getAllDataClasses2');
            $mauroDataClassesResponse = Mauro::getAllDataClasses($this->datasetId);
            // var_dump('$mauroDataClassesResponse', $mauroDataClassesResponse);
            // $this->deleteAllDataClasses();
            var_dump($mauroDataClassesResponse['items']);
            foreach ($classesToAdd as $className){
                // Find the correct entry from the incoming request
                $classesToAddContent = $this->getByName($className, $data['structuralMetadata']);
                
                var_dump('classesToAddContent', $classesToAddContent);
                // Create the Mauro DataClass, and its child DataElements
                if ($classesToAddContent) {
                    $mauroCreateResponse = Mauro::createDataClass($this->datasetId, $classesToAddContent['name'], $classesToAddContent['description']);
                    var_dump($mauroCreateResponse);
                    var_dump('getAllDataClasses3');
                    // TO DELETE: this is just for debug
                    $mauroDataClassesResponse = Mauro::getAllDataClasses($this->datasetId);
                    // var_dump('$mauroDataClassesResponse', $mauroDataClassesResponse);
                    // $this->deleteAllDataClasses();
                    var_dump($mauroDataClassesResponse['items']);

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
                $classesToCheckContent = $this->getByName($className, $data['structuralMetadata']);

                // Get the appropriate entry from the Mauro list
                $classToCheckContentMauro = $this->getByLabel($className, $mauroDataClassesResponse['items']);
                
                var_dump('classesToCheckContent', $classesToCheckContent);
                var_dump('classToCheckContentMauro', $classToCheckContentMauro);
                if ($classesToCheckContent) {
                    var_dump('processing elements from this class:', $classesToCheckContent['name']);
                    // Update the DataClass in Mauro - in case the details have been modified
                    var_dump('we will update the following details:', $this->datasetId, $classesToCheckContent['name'], $classesToCheckContent['description'], $classToCheckContentMauro['id']);
                    $mauroUpdateResponse = Mauro::updateDataClass($this->datasetId, $classesToCheckContent['name'], $classesToCheckContent['description'], $classToCheckContentMauro['id']);
                    var_dump($mauroUpdateResponse);
                    var_dump('getAllDataClasses4');

                    // TO DELETE: this is just for debug
                    $mauroDataClassesResponse = Mauro::getAllDataClasses($this->datasetId);
                    // var_dump('$mauroDataClassesResponse', $mauroDataClassesResponse);
                    // $this->deleteAllDataClasses();
                    var_dump($mauroDataClassesResponse['items']);

                    // TODO: rename
                    $dataElementsContentMauro = Mauro::getAllDataElements($this->datasetId, $classToCheckContentMauro['id'])['items'];
                    // Now that the dataClass has been updated, we need to check all child DataElements 
                    // against the request, to see which need deletion/creation/update.
                    /*
                    * Calculate the 3 categories of DataElements to be handled
                    */
                    $elementsToAdd = array_diff(array_column($classesToCheckContent['columns'], 'name'), 
                                                array_column($dataElementsContentMauro, 'label'));
                    var_dump('elementsToAdd', $elementsToAdd);
                    // var_dump('calced1 from:', $classesToCheckContent, $dataElementsContentMauro);
                    // var_dump('calced from', array_column($classesToCheckContent['columns'], 'name'), 'and', array_column($dataElementsContentMauro, 'label'));
                    $elementsToDelete = array_diff(array_column($dataElementsContentMauro, 'label'), 
                                                   array_column($classesToCheckContent['columns'], 'name'));
                    var_dump('elementsToDelete', $elementsToDelete);
                    $elementsToCheck = array_intersect(array_column($dataElementsContentMauro, 'label'), 
                                                       array_column($classesToCheckContent['columns'], 'name'));
                    var_dump('elementsToCheck', $elementsToCheck);

                    // Handle the "add" DataElements which are present in the incoming request but not in the existing Mauro DataClass.
                    foreach ($elementsToAdd as $elementName){

                        // Find the correct entry from the incoming request
                        var_dump('to getbyname:', $className, $data['structuralMetadata']);
                        var_dump('getbyname', $this->getByName($className, $data['structuralMetadata']));
                        $elementToAddContent = $this->getByName($elementName, $this->getByName($className, $data['structuralMetadata'])['columns']);
                                                            
                        var_dump('elementToAddContent', $elementToAddContent);
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
                        var_dump('hello');
                        var_dump($this->getByName($className, $data['structuralMetadata']));
                        var_dump($dataElementsContentMauro);
                        var_dump($this->getByLabel($elementName, $dataElementsContentMauro));

                        $elementToCheckContent = $this->getByName($elementName, $this->getByName($className, $data['structuralMetadata'])['columns']);
                        $elementToCheckContentMauro = $this->getByLabel($elementName, $dataElementsContentMauro);
                        var_dump('elementToCheckContent', $elementToCheckContent);
                        if ($elementToCheckContent) {
                            var_dump($this->datasetId, 
                                $elementToCheckContent['name'], 
                                $elementToCheckContent['description'], 
                                $classToCheckContentMauro['id'], 
                                $elementToCheckContentMauro['id']);
                            $mauroUpdateResponse = Mauro::updateDataElement($this->datasetId, 
                                                                            $elementToCheckContent['name'], 
                                                                            $elementToCheckContent['description'], 
                                                                            $classToCheckContentMauro['id'], 
                                                                            $elementToCheckContentMauro['id']);
                            var_dump($mauroUpdateResponse);
                         }
                         $allDataElements = Mauro::getAllDataElements($this->datasetId, $classToCheckContentMauro['id']);
                         var_dump($allDataElements);
                    }

                    foreach ($elementsToDelete as $elementName){

                        var_dump('dataElementsContentMauro', $dataElementsContentMauro);
                        var_dump('labelled:', $this->getByLabel($elementName, $dataElementsContentMauro));
                        $elementToDeleteContent = $this->getByLabel($elementName, $dataElementsContentMauro);

                        var_dump('elementToDeleteContent', $elementToDeleteContent);
                        var_dump("let's delete class", $classToCheckContentMauro['id'], "and element", $elementToDeleteContent['id']);
                        if ($elementToDeleteContent) {
                            $mauroDeleteResponse = Mauro::deleteDataElement($elementToDeleteContent['id'],
                                                                            $this->datasetId, 
                                                                            $classToCheckContentMauro['id']);
                            var_dump($mauroDeleteResponse);
                        }
                        var_dump('deletion completed');
                    }
                }
            }
        }

        var_dump('hierarchy:');
        $hierarchyResponse = Mauro::getDataModelHierarchy($this->datasetId);
        var_dump($hierarchyResponse);
        $tedUrl = env('TED_SERVICE_URL');
        $tedEnabled = env('TED_ENABLED');
        var_dump("envs set");

        if (!empty($tedUrl) && $tedEnabled) {
            $this->postToTermExtractionDirector(json_encode($data));
        }
        // var_dump("here7");

        MMC::reindexElastic($data, $this->datasetId);
        // var_dump("here8");
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
            // var_dump("herea0");

            $response = Http::withBody(
                $dataset,
                'application/json'
            )->post(env('TED_SERVICE_URL'));
            // var_dump("herea1");
            if (array_key_exists('extracted_terms', $response->json())) {
                foreach ($response->json()['extracted_terms'] as $n) {
                    // var_dump("herea2");

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
            // var_dump("herea3");

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
