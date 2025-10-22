<?php

namespace App\Mauro;

use Exception;
use App\Exceptions\MauroServiceException;
use Illuminate\Support\Facades\Http;

class Mauro
{
    /**
     * Returns all folders as JSON from Mauro
     *
     * @return array
     */
    public function getFolders(): array
    {
        $url = config('MAURO_API_URL');
        $url .= '/folders';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
        $url = config('MAURO_API_URL');
        $url .= '/folders/' . $id;

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
        $url = config('MAURO_API_URL');
        $url .= '/folders/' . $parentId . '/folders';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
        $url = config('MAURO_API_URL');
        $url .= '/dataModels/';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

            return $response->json();

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Returns a list of DataModels (datasets) from Mauro by Id
     *
     * @param string $datasetId     The dataset ID to return dataset
     * @return array                Returns entire response from Mauro Data Mapper as an array
     */
    public function getDatasetById(string $datasetId): array
    {
        $url = config('MAURO_API_URL');
        $url .= '/dataModels/' . $datasetId;
        $overallResponse = [];

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
                ->acceptJson()
                ->get($url);

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
     * Returns a list of DataModels (datasets) from Mauro by Id and Profile
     *
     * @param string $datasetId     The dataset ID to return dataset
     * @return array                Returns entire response from Mauro Data Mapper as an array
     */
    public function getDatasetByIdProfile(string $datasetId): array
    {
        $url = config('MAURO_API_URL');
        $url .= '/dataModels/' . $datasetId . '/profile/uk.ac.ox.softeng.maurodatamapper.profile.provider/Template/3.0.0';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
                ->acceptJson()
                ->get($url);

            return $response->json();
        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Returns a list of DataModel (dataset) from Mauro by Id with metadata and with max 1000 items
     *
     * @param string $datasetId     The dataset ID to return dataset
     * @return array                Returns entire response from Mauro Data Mapper as an array
     */
    public function getDatasetByIdMetadata(string $datasetId): array
    {
        $url = config('MAURO_API_URL');
        $url .= '/dataModels/' . $datasetId . '/metadata?max=1000';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
        $url = config('MAURO_API_URL');
        $url .= '/dataModels/' . $parentId . '/dataModels';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function createFolder(string $label, string $description, string $parentFolderId = ''): array
    {
        $postUrl = config('MAURO_API_URL');

        if ($parentFolderId !== '') {
            $postUrl .= '/folders/' . $parentFolderId . '/folders';
        } else {
            $postUrl .= '/folders';
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
        $postUrl = config('MAURO_API_URL');

        if ($parentFolderId !== '') {
            $postUrl .= '/folders/' . $parentFolderId . '/folders/' . $id . '?permanent=' . $permanentDeletion;
        } else {
            $postUrl .= '/folders/' . $id . '?permanent=' . $permanentDeletion;
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
     * @param string $author            Represents the Author name associated with this data model
     * @param string $organisation      Represents the Organisation associated with this author for this data model
     * @param string $parentFolderId    Represents the parent folder id to create this data model under
     *
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function createDataModel(string $label, string $description, string $author, string $organisation, string $parentFolderId, array $jsonObj): array
    {
        $overallResponse = [];

        $postUrl = config('MAURO_API_URL');
        $postUrl .= '/folders/' . $parentFolderId . '/dataModels';

        try {
            // Structural meta data held within the incoming dataset, will
            // need to be created as a Schema->DataClass, and not be held
            // along with the summary metadata
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
     * Update Data Model within Mauro Data Mapper for $dataModelId
     *
     * @param string $label             Represents the short text associated with this data model
     * @param string $description       Represents the long text associated with this data model
     * @param string $author            Represents the Author name associated with this data model
     * @param string $organisation      Represents the Organisation associated with this author for this data model
     * @param string $parentFolderId    Represents the parent folder id to create this data model under
     * @param string $dataModelId       Represents data model id
     *
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function updateDataModel(string $label, string $description, string $author, string $organisation, string $parentFolderId, array $jsonObj, string $dataModelId): array
    {
        $overallResponse = [];

        $url = config('MAURO_API_URL') . '/dataModels/' . $dataModelId;

        try {
            // Structural meta data held within the incoming dataset, will
            // need to be created as a Schema->DataClass, and not be held
            // along with the summary metadata
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
                ->acceptJson()
                ->put($url, [
                    'label' => $label,
                    'description' => $description,
                    'author' => $author,
                    'organisation' => $organisation,
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

    // PUT https://api.dev.hdruk.dev/mauro/api/dataModels/d0a30639-14eb-4ecc-9690-980cc0a520c8/newBranchModelVersion
    // {asynchronous: false}
    public function duplicateDataModel(string $id)
    {
        $url = config('MAURO_API_URL').'/dataModels/' . $id . '/newBranchModelVersion';
        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
                ->acceptJson()
                ->put($url, [
                    'asynchronous' => false
                ]);

            return $response->json();
        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Creates a new Data Model within Mauro Data Mapper underneath $parentFolderId, and mostly
     * acts the same as the function above, but with a slight difference in that the type of
     * this model is set to Data Standard to define a dynamic profile within mauro which
     * maps to a particular schema shape
     *
     * @param string $label             Represents the short text associated with this data model
     * @param string $description       Represents the long text associated with this data model
     * @param string $author            Represents the author name associated with this data model
     * @param string $organisation      Represents the organisation associated with this author for this data model
     * @param string $parentFolderId    Represents the parent folder id to create this data model under
     * @param array $jsonObj            Represents the schema to be created for this data model
     *
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function createDataStandard(string $label, string $description, string $author, string $organisation, string $parentFolderId, array $jsonObj): array
    {
        $overallResponse = [];

        $postUrl = config('MAURO_API_URL');
        $postUrl .= '/folders/' . $parentFolderId . '/dataModels';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->post($postUrl, [
                'label' => $label,
                'description' => $description,
                'author' => $author,
                'organisation' => $organisation,
                'type' => 'Data Standard',
                'classifiers' => [],
                'metadata' => $this->makeDataStandardMetadata($jsonObj),
            ]);

            $overallResponse['DataModel'] = [
                'responseJson' => $response->json(),
                'responseStatus' => $response->status(),
            ];

            if ($overallResponse['DataModel']['responseStatus'] === 201) {
                // Successfully created the data model. Now create class and elements
                $map = $this->makeDataStandardDataClassAndDataElementsMap($jsonObj);
                $parentId = $overallResponse['DataModel']['responseJson']['id'];

                foreach ($map as $class) {
                    $responseClass = $this->createDataClass($parentId, $class['label'], $class['description']);
                    foreach ($class['elements'] as $element) {
                        if (isset($responseClass['id'])) {
                            $this->createDataElement($parentId, $responseClass['id'], $element['label'], $element['description'], 'string');
                        }
                    }
                }
            }

            return $overallResponse;

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Deletes an existing DataModel from Mauro
     *
     * @param string $id                The ID of the DataModel to delete
     * @param string $permanentDeletion Whether or not this model is deleted permanently
     *
     * @return bool                     Whether the operation completed successfully or not
     */
    public function deleteDataModel(string $id, string $permanentDeletion = 'false'): bool
    {
        $url = config('MAURO_API_URL');
        $url .= '/dataModels/' . $id . '?permanent=' . $permanentDeletion;

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->delete($url);

            return ($response->status() === 204);

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Restores a soft deleted DataModel in Mauro
     *
     * @param string $id                The ID of the DataModel to restore
     *
     * @return bool                     Whether the operation completed successfully or not
     */
    public function restoreDataModel(string $id): bool
    {
        $url = config('MAURO_API_URL');
        $url .= '/admin/dataModels/' . $id . '/undoSoftDelete';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->put($url);

            return ($response->status() === 200);

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
        $postUrl = config('MAURO_API_URL');
        $postUrl .= '/dataModels/' . $parentModelId . '/dataClasses';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
     * Update a DataClass object within Mauro to store structural metadata against a model
     *
     * @param string $parentModelId     Represents the model which owns this data class
     * @param string $name              Represents the name of this data class
     * @param string $description       Represents the description of this data class
     * @param string $dataClassId       Represents the data class to update
     *
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function updateDataClass(string $parentModelId, string $name, string $description, string $dataClassId): array
    {
        try {
            $url = config('MAURO_API_URL') . '/dataModels/' . $parentModelId . '/dataClasses/' . $dataClassId;

            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
                ->acceptJson()
                ->put($url, [
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
     * Get All DataClasses object within Mauro
     *
     * @param string $parentModelId     Represents the model which owns this new data class
     *
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function getAllDataClasses(string $parentModelId): array
    {
        try {
            $url = config('MAURO_API_URL') . '/dataModels/' . $parentModelId . '/dataClasses';

            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

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
     * @return bool                 Returns whether Mauro Data Mapper deleted an element as a boolean value
     */
    public function deleteDataClass(string $id, string $parentModelId): bool
    {
        $url = config('MAURO_API_URL') . '/dataModels/' . $parentModelId . '/dataClasses/' . $id;

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->delete($url);

            return ($response->status() === 204);

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
        $postUrl = config('MAURO_API_URL');
        $postUrl .= '/dataModels/' . $parentModelId . '/dataClasses/' . $parentDataClassId . '/dataElements';

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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
    * Update a DataElement object within Mauro to store structural metadata against a model
    *
    * @param string $parentModelId     Represents the model which owns this new data class
    * @param string $name              Represents the name of this data class
    * @param string $description       Represents the description of this data class
    * @param string $dataClassId       Represents the data class which owns this data element
    * @param string $dataElementId     Represents the data element to update
    *
    * @return array                    Returns entire response from Mauro Data Mapper as an array
    */
    public function updateDataElement(string $parentModelId, string $name, string $description, string $dataClassId, string $dataElementId): array
    {
        try {
            $url = config('MAURO_API_URL') . '/dataModels/' . $parentModelId . '/dataClasses/' . $dataClassId . '/dataElements/' . $dataElementId;

            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
                ->acceptJson()
                ->put($url, [
                    'label' => $name,
                    'description' => $description,
                    'model' => $parentModelId,
                    'dataClass' => $dataClassId,
                    'domainType' => 'DataElement',
                ]);

            return $response->json();
        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }
    /**
     * Get All DataElements object within Mauro against an existing model and data class
     *
     * @param string $parentModelId     Represents the parent model which owns this data element
     * @param string $parentDataClassId Represents the parent data class which owns this data element
     *
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function getAllDataElements(string $parentModelId, string $parentDataClassId)
    {
        try {
            $url = config('MAURO_API_URL') . '/dataModels/' . $parentModelId . '/dataClasses/' . $parentDataClassId . '/dataElements';

            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

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
     * @return bool                         Returns whether Mauro Data Mapper deleted an element as a bool
     */
    public function deleteDataElement(string $id, string $parentModelId, string $parentDataClassId): bool
    {
        $postUrl = config('MAURO_API_URL');
        $postUrl .= '/dataModels/' . $parentModelId . '/dataClasses/' . $parentDataClassId . '/dataElements/' . $id;

        try {
            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->delete($postUrl);

            return ($response->status() === 204);

        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
    }

    /**
     * Get All DataClasses and DataElements object within a DataModel
     *
     * @param string $dataModelId       Represents the data model to query
     *
     * @return array                    Returns entire response from Mauro Data Mapper as an array
     */
    public function getDataModelHierarchy(string $dataModelId): array
    {
        try {
            $url = config('MAURO_API_URL') . '/dataModels/' . $dataModelId . '/hierarchy';

            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
            ])
            ->acceptJson()
            ->get($url);

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
    public function finaliseDataModel(string $id, string $semverChange = 'major', string $semverVersion = ''): array
    {
        $url = config('MAURO_API_URL');
        $url .= '/dataModels/' . $id . '/finalise';

        $payload = [];

        try {

            if ($semverChange !== 'custom') {
                $payload['versionChangeType'] = $semverChange;
            } else {
                $payload['version'] = $semverVersion;
            }

            $response = Http::withHeaders([
                'apiKey' => config('MAURO_APP_KEY'),
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

        foreach (Config('metadata') as $key => $path) {
            $tmpArray[] = $this->makeMetadataElement($key, $this->rootStringToObjectMapping($path, $obj['dataset']));
        }

        return $tmpArray;
    }

    /**
     * Returns an array defining the shape of the incoming schema, ready for importing
     * as a DataStandard within Mauro. It should be noted that this is different
     * from the function below, in that this defines the metadata paths for
     * each element of the schema for Mauro to map and validate against
     *
     * @param array $schema     The incoming metadata definition schema
     *
     * @return array
     */
    private function makeDataStandardMetadata(array $schema): array
    {
        $tmpArray = [];
        $forcedTitle = null;

        foreach ($schema['$defs'] as $key => $shape) {
            if (isset($shape['properties'])) {
                foreach ($shape['properties'] as $element => $value) {
                    if (!isset($value['title'])) {
                        $forcedTitle = preg_replace('/(?<!\ )[A-Z]/', ' $0', $element);
                    }

                    $tmpArray[] = [
                        'namespace' => 'hdruk.profile',
                        'key' => sprintf('properties/%s/%s', strtolower($key), $element),
                        'value' => (isset($value['title']) ? ucwords($value['title']) : ucwords($forcedTitle)),
                    ];
                }
            }
        }

        return $tmpArray;
    }

    /**
     * Similar to the above, but this function returns the _entire_ shape of the schema as an
     * importable array into Mauro. The main difference being that this function not only
     * defines the classes to be built, but also the elements held underneath those
     * classes.
     *
     * @param array $schema     The incoming metadata definition schema
     *
     * @return array
     */
    private function makeDataStandardDataClassAndDataElementsMap(array $schema): array
    {
        try {
            $tmpArray = [];
            $forcedTitle = null;

            foreach ($schema['$defs'] as $key => $shape) {
                if (isset($shape['properties'])) {
                    $currentElement = [
                        'label' => $key,
                        'description' => $shape['title'],
                        'dataType' => 'DataClass',
                        'elements' => [],
                    ];
                    foreach ($shape['properties'] as $element => $value) {
                        if (!isset($value['title'])) {
                            $forcedTitle = preg_replace('/(?<!\ )[A-Z]/', ' $0', $element);
                        }

                        $currentElement['elements'][] = [
                            'label' => (isset($value['title']) ? $value['title'] : $forcedTitle),
                            'description' => (isset($value['description']) ? $value['description'] : ''),
                            'dataType' => 'string',
                        ];
                    }

                    $tmpArray[] = $currentElement;
                }
            }

            return $tmpArray;
        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
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
        try {
            return [
                'namespace' => 'hdruk.profile', // TODO - This shouldn't be hardcoded fine while testing implementation
                'key' => $key,
                'value' => $value,
            ];
        } catch (Exception $e) {
            throw new MauroServiceException($e->getMessage());
        }
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
        $temp = &$obj;
        $tokenPath = explode('/', $route);
        foreach ($tokenPath as $key) {
            $temp = &$temp[$key];
        }

        return $temp;
    }
}
