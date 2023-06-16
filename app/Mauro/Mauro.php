<?php

namespace App\Mauro;

use App\Exceptions\MauroServiceException;

use Illuminate\Support\Facades\Http;

class Mauro {

    private $metadataTranslationMap = [
        // Summary
        'properties/summary/title'                                              => 'data/datasetv2/summary/title',
        'properties/summary/abstract'                                           => 'data/datasetv2/summary/abstract',
        'properties/summary/publisher/contactPoint'                             => 'data/datasetv2/summary/publisher/contactPoint',
        'properties/summary/keywords'                                           => 'data/datasetv2/summary/keywords',
        'properties/summary/publisher/name'                                     => 'data/datasetv2/summary/publisher/name',
        'properties/summary/publisher/memberOf'                                 => 'data/datasetv2/summary/publisher/memberOf',
        'properties/summary/doiName'                                            => 'data/datasetv2/summary/doiName',

        // Documentation
        'properties/documentation/description'                                  => 'data/datasetv2/documentation/description',
        'properties/documentation/isPartOf'                                     => 'data/datasetv2/documentation/isPartOf',
        'properties/documentation/associatedMedia'                              => 'data/datasetv2/documentation/associatedMedia',

        // Provenance
        'properties/provenance/temporal/accrualPeriodicity'                     => 'data/datasetv2/provenance/temporal/accrualPeriodicity',
        'properties/provenance/temporal/distributionReleaseDate'                => 'data/datasetv2/provenance/temporal/distributionReleaseDate',
        'properties/provenance/origin/source'                                   => 'data/datasetv2/provenance/origin/source',
        'properties/provenance/origin/collectionSituation'                      => 'data/datasetv2/provenance/origin/collectionSituation',
        'properties/provenance/temporal/startDate'                              => 'data/datasetv2/provenance/temporal/startDate',
        'properties/provenance/origin/purpose'                                  => 'data/datasetv2/provenance/origin/purpose',
        'properties/provenance/temporal/timeLag'                                => 'data/datasetv2/provenance/temporal/timeLag',
        'properties/provenance/temporal/endDate'                                => 'data/datasetv2/provenance/temporal/endDate',
        
        // Coverage
        'properties/coverage/pathway'                                           => 'data/datasetv2/coverage/pathway',
        'properties/coverage/spatial'                                           => 'data/datasetv2/coverage/spatial',
        'properties/coverage/followup'                                          => 'data/datasetv2/coverage/followup',
        'properties/coverage/physicalSampleAvailability'                        => 'data/datasetv2/coverage/physicalSampleAvailability',
        'properties/coverage/typicalAgeRange'                                   => 'data/datasetv2/coverage/typicalAgeRange',

        // Accessibility
        'properties/accessibility/usage/dataUseLimitation'                      => 'data/datasetv2/accessibility/usage/dataUseLimitation',
        'properties/accessibility/access/deliveryLeadTime'                      => 'data/datasetv2/accessibility/access/deliveryLeadTime',
        'properties/accessibility/usage/investigations'                         => 'data/datasetv2/accessibility/usage/investigations',
        'properties/accessibility/access/dataProcessor'                         => 'data/datasetv2/accessibility/access/dataProcessor',
        'properties/accessibility/formatAndStandards/vocabularyEncodingScheme'  => 'data/datasetv2/accessibility/formatAndStandards/vocabularyEncodingScheme',
        'properties/accessibility/formatAndStandards/format'                    => 'data/datasetv2/accessibility/formatAndStandards/format',
        'properties/accessibility/formatAndStandards/conformsTo'                => 'data/datasetv2/accessibility/formatAndStandards/conformsTo',
        'properties/accessibility/access/dataController'                        => 'data/datasetv2/accessibility/access/dataController',
        'properties/accessibility/usage/dataUseRequirements'                    => 'data/datasetv2/accessibility/usage/dataUseRequirements',
        'properties/accessibility/usage/isReferencedBy'                         => 'data/datasetv2/accessibility/usage/isReferencedBy',
        'properties/accessibility/access/accessRights'                          => 'data/datasetv2/accessibility/access/accessRights',
        'properties/accessibility/access/jurisdiction'                          => 'data/datasetv2/accessibility/access/jurisdiction',
        'properties/accessibility/access/accessRequestCost'                     => 'data/datasetv2/accessibility/access/accessRequestCost',
        'properties/accessibility/access/accessService'                         => 'data/datasetv2/accessibility/access/accessService',
        'properties/accessibility/formatAndStandards/language'                  => 'data/datasetv2/accessibility/formatAndStandards/language',
        'properties/accessibility/usage/resourceCreator'                        => 'data/datasetv2/accessibility/usage/resourceCreator',

        // Enrichment and Linkage
        'properties/enrichmentAndLinkage/derivation'                            => 'data/datasetv2/enrichmentAndLinkage/derivation',
        'properties/enrichmentAndLinkage/qualifiedRelation'                     => 'data/datasetv2/enrichmentAndLinkage/qualifiedRelation',
        'properties/enrichmentAndLinkage/tools'                                 => 'data/datasetv2/enrichmentAndLinkage/tools',

        // Observations
        //'properties/summary/observations' => 'data/datasetv2/observations',
        /*
            TODO - Investigate this. Removed for now as it causes this exception:

            array:7 [ // app/Console/Commands/MauroConduit.php:47
                "status" => 422
                "reason" => "Unprocessable Entity"
                "errorCode" => "UEX--"
                "message" => "Validation error whilst flushing entity [uk.ac.ox.softeng.maurodatamapper.core.facet.Metadata]:"
                "path" => "/mauro/api/folders/0930397d-6f49-4f56-b41f-499da24e35b8/dataModels"
                "version" => "5.3.0"
                "validationErrors" => array:2 [
                    "total" => 1
                    "errors" => array:1 [
                        0 => array:1 [
                            "message" => "No converter found capable of converting from type [org.apache.groovy.json.internal.LazyMap] to type [java.lang.String]"
                        ]
                    ]
                ]
            ]
        */
    ];

    /**
     * Returns all folders as JSON from Mauro
     * 
     * @return array
     */
    public function getFolders(): array
    {
        $url = env('MAURO_API_URL');
        $url .= '/folders';

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Returns folder by ID from Mauro
     * 
     * @param string $id                The ID of the folder to return
     * 
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function getFolderById(string $id): array
    {
        $url = env('MAURO_API_URL');
        $url .= '/folders/' . $id;

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Returns a list of folders underneath the $parentId from Mauro
     * 
     * @param string $parentId          The ID of the parent to list Folders from
     * 
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function getFoldersByParentId(string $parentId): array
    {
        $url = env('MAURO_API_URL');
        $url .= '/folders/' . $parentId . '/folders';

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Returns a list of DataModels (datasets) from Mauro
     * 
     * @return array                Returns entire response from Mauro Data Mapper as an array
     */
    public function getDatasets(): array
    {
        $url = env('MAURO_API_URL');
        $url .= '/dataModels';

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Returns a list of DataModels (datasets) from Mauro by parent ID
     * 
     * @param string $parentId              The parent ID to return datasets from
     * 
     * @return array
     */
    public function getDatasetsByFolder(string $parentId): array
    {
        $url = env('MAURO_API_URL');
        $url .= '/dataModels/' . $parentId . '/dataModels';

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Creates a folder (Publisher) within Mauro Data Mapper underneath our internal Publisher folder
     * 
     * @param string $label             Represents the short name for this folder
     * @param string $description       Represents the long description for this folder
     * @param string $parentFolderId    If set, this new folder will be created underneath this parent folder
     * 
     * @return string                   Returns entire response from Mauro Data Mapper as an array
     */
    public function createFolder(string $label, string $description, string $parentFolderId = ''): array
    {
        $postUrl = env('MAURO_API_URL');

        if ($parentFolderId !== '') {
            $postUrl .= '/folders/' . $parentFolderId . '/folders';
        } else {
            $postUrl .= '/folders';
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl, [
                'label' => $label,
                'description' => $description,
                'readableByAuthenticatedUsers' => true,
            ]);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Deletes a folder held within Mauro Data Mapper underneath our internal Publisher folder
     * 
     * @param string $id                    Represents the ID of the folder to be deleted
     * @param string $permanentDeletion     Whether to hard or soft delete this folder, defaults to hard deletion
     * @param string $parentFolderId        Represents the parent folder id housing this folder
     * 
     * @return bool                         Returns true on successful deletion, false otherwise
     */
    public function deleteFolder(string $id, string $permanentDeletion = 'true', string $parentFolderId = ''): bool
    {
        $postUrl = env('MAURO_API_URL');

        if ($parentFolderId !== '') {
            $postUrl .= '/folders/' . $parentFolderId . '/folders/' . $id . '?permanent=' . $permanentDeletion;
        } else {
            $postUrl .= '/folders/' . $id . '?permanent=' . $permanentDeletion;
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->delete($postUrl);

            return ($response->status() === 204);

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Creates a new Data Model within Mauro Data Mapper underneath $parentFolderId
     * 
     * @param string $label             Represents the short text associated with this data model
     * @param string $description       Represents the long text associated with this data model
     * @param string $autho             Represents the Author name associated with this data model
     * @param string $organisation      Represents the Organisation associated with this author for this data model
     * @param string $parentFolderId    Represents the parent folder id to create this data model under
     * 
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function createDataModel(string $label, string $description, string $author, string $organisation, string $parentFolderId, array $jsonObj): array
    {
        $overallResponse = [];

        $postUrl = env('MAURO_API_URL');
        $postUrl .= '/folders/' . $parentFolderId . '/dataModels';

        try {
            // Structural meta data held within the incoming dataset, will
            // need to be created as a Schema->DataClass, and not be held
            // along with the summary metadata
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl, [
                'label' => $label,
                'description' => $description,
                'author' => $author,
                'organisation' => $organisation,
                'type' => 'Data Asset', // This is always the case when creating a data model
                'classifiers' => [],
                // This is required to align the HDR schema profile to the data model under Mauro.
                // Presumably this is mainly driven by `namespace` here, but documentation is lacking
                // as far as this metadata object data is concerned
                'metadata' => $this->generateMetadataFromMap($jsonObj),
            ]);

            $overallResponse['DataModel'] = [
                'responseJson' => $response->json(),
                'responseStatus' => $response->status(),
            ];

            return $overallResponse;

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Deletes an existing DataModel from Mauro
     * 
     * @param string $id                The ID of the DataModel to delete
     * @param string $parentFolderId    The ID of the parent Folder to delete from 
     * 
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function deleteDataModel(string $id, string $permanentDeletion = 'true'): bool
    {
        $url = env('MAURO_API_URL');
        $url .= '/dataModels/' . $id . '?permanent=' . $permanentDeletion;

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->delete($url);

            return ($response->status() === 204);

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Creates a new DataClass object within Mauro to store structural metadata against a model
     * 
     * @param string $parentModelId     Represents the model which owns this new data class
     * @param string $name              Represents the name of this data class
     * @param string $description       Represents the description of this data class
     * 
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function createDataClass(string $parentModelId, string $name, string $description): array
    {
        $postUrl = env('MAURO_API_URL');
        $postUrl .= '/dataModels/' . $parentModelId . '/dataClasses';

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl, [
                'label' => $name,
                'description' => $description,
                'model' => $parentModelId,
                'dataType' => 'DataClass',
            ]);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Deletes a DataClass object within Mauro
     * 
     * @param string $id            The ID of the DataClass to be deleted
     * @param string $parentModelId The ID of the parent DataModel to delete this DataClass from
     * 
     * @return array                Returns entire response from Mauro Data Mapper as an array
     */
    public function deleteDataClass(string $id, string $parentModelId): array
    {
        $postUrl = env('MAURO_API_URL');
        $postUrl .= '/dataModels/' . $parentModelId . '/dataClasses/' . $id;

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Creates a new DataElement object within Mauro against an existing model and data class
     * 
     * @param string $parentModelId     Represents the parent model which owns this data element
     * @param string $parentDataClassId Represents the parent data class which owns this data element
     * @param string $name              Represents the name of this data element
     * @param string $description       Represents the description of this data element
     * 
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function createDataElement(string $parentModelId, string $parentDataClassId, string $name, string $description, string $type): array
    {
        $postUrl = env('MAURO_API_URL');
        $postUrl .= '/dataModels/' . $parentModelId . '/dataClasses/' . $parentDataClassId . '/dataElements'; 

        try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl, [
                'label' => $name,
                'description' => $description,
                'dataClass' => $parentDataClassId,
                'domainType' => 'DataElement',
                'dataType' => [
                    'domainType' => 'PrimitiveType',
                    'label' => $type,
                ],
            ]);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Deletes an existing DataElement from Mauro under $parentModelId and $parentDataClassId
     * 
     * @param string $id                    The ID of the DataElement to delete
     * @param string $parentModelId         The ID of the parent DataModel to delete this DataElement from
     * @param string $parentDataClassId     The ID of the parent DataClass to delete this DataElement from
     * 
     * @return array                        Returns entire response from Mauro Data Mapper as an array
     */
    public function deleteDataElement(string $id, string $parentModelId, string $parentDataClassId): array
    {
       $postUrl = env('MAURO_API_URL');
       $postUrl .= '/dataModels/' . $parentModelId . '/dataClasses/' . $parentDataClassId . '/dataElements/' . $id;

       try {
            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl);

            return $response->json();

       } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
       }
    }

    /**
     * Finalises and makes ready a pre-existing DataModel within Mauro
     * 
     * @param string $id                The ID of the DataModel to finalise
     * @param string $semverChange      The version change string, can be either Major, Minor or Patch
     * @param string $semverVersion     The semver version string to set, in the form of Major.Minor.Patch
     *          It is possible to send a $semverChange of 'Custom' and provide a $semverVersion that goes
     *          against traditional semver formatting of Major.Minor.Patch. This allows formats such as
     *          A.B.C.Z if you so wish
     * 
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function finaliseDataModel(string $id, string $semverChange='', string $semverVersion = ''): array
    {
        $url = env('MAURO_API_URL');
        $url .= '/dataModels/' . $id . '/finalise';

        $payload = [];

        try {

            if ($semverChange !== '' && $semverVersion !== '') {
                $payload = [
                    'versionChange' => $semverChange,
                    'version' => $semverVersion,
                ];
            } else {
                $payload = [
                    'versionChange' => 'major',
                ];
            }

            $response = Http::withHeaders([
                'apiKey' => env('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->put($url, $payload);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }


    /**
     * Returns a translated array of metadata via map of Mauro profile paths vs HDRUK schema paths
     * 
     * @param array $obj        The incoming metadata decoded from JSON
     * 
     * @return array
     */
    private function generateMetadataFromMap(array $obj): array
    {
        $tmpArray = [];

        foreach ($this->metadataTranslationMap as $key => $path) {
            $tmpArray[] = $this->makeMetadataElement($key, $this->rootStringToObjectMapping($path, $obj));
        }

        return $tmpArray;
    }

    /**
     * Returns an array forming a Mauro metadata element
     * 
     * @param string $key       The key in which to save this value against
     * @param mixed $value      The value to store against this key
     * 
     * @return array
     */
    private function makeMetadataElement(string $key, mixed $value): array
    {
        return [
            'namespace' => 'Testing.mauro', // TODO - This shouldn't be hardcoded fine while testing implementation
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * Returns an embedded array element by string to avoid massively duplicated code
     * 
     * @param string $route     A / delimited string denoting the array path to return
     * @param array $obj        The array to scan and return the value from based upon $route
     * 
     * @return mixed
     */
    private function rootStringToObjectMapping(string $route, array $obj): mixed
    {
        // Allows us to traverse an array in php via string route to return a value, eg:
        //      'data/something/somethingElse/theElementWeWant'
        // equates to:
        //      $obj['data']['something']['somethingElse']['theElementWeWant']
        $temp =& $obj;
        $tokenPath = explode('/', $route);
        foreach ($tokenPath as $key) {
            $temp =& $temp[$key];
        }

        return $temp;
    }
}