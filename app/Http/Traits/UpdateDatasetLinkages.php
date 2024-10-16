<?php

namespace App\Http\Traits;

use Config;
use Auditor;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Publication;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\PublicationHasDatasetVersion;
use App\Http\Traits\GetValueByPossibleKeys;

trait UpdateDatasetLinkages
{
    use GetValueByPossibleKeys;

    /**
     * Create SQL linkages for tools, publications, and dataset relationships.
     * 
     * This method processes and creates SQL linkages for tools, publications, 
     * and dataset relationships (such as isDerivedFrom, isPartOf, linkedDatasets, etc.)
     * based on the latest metadata version of a dataset.
     *
     * If the $delete flag is set to true, existing linkages for all dataset versions
     * will be deleted prior to creating new ones.
     *
     * @param Dataset $dataset The dataset for which linkages are created.
     * @param bool $delete Optional flag to delete existing linkages. Defaults to false.
     * @return void
     */
    public function createSqlLinkage(Dataset $dataset, bool $delete = false): void
    {
        // Retrieve the latest dataset_version for the given dataset_id
        $version = $dataset->latestVersion();

        // Retrieve all dataset_version_ids for the given dataset_id
        $allDatasetVersionIds = DatasetVersion::where('dataset_id', $dataset->id)->select('id');

        // Build the search array that will be used for finding matching datasets
        $datasetSearchArray = $this->buildDatasetSearchArray();

        // If delete is set to true, remove all existing linkages for the dataset
        if ($delete) {
            $this->removeExistingLinkages($allDatasetVersionIds);
        }

        // Log the dataset processing
        Log::info("Processing Dataset: $dataset->id");
                
        // Process linkages for tools, publications, and dataset relationships
        $this->processTools($version);
        $this->processPublications($version, 'publicationUsingDataset', 'USING');
        $this->processPublications($version, 'publicationAboutDataset', 'ABOUT');

        // Process dataset relationships like isDerivedFrom, isPartOf, and linkedDatasets
        $this->processDatasetRelationship($version, 'isDerivedFrom', 'isDerivedFrom', 'Derived From', $datasetSearchArray);
        $this->processDatasetRelationship($version, 'isPartOf', 'isPartOf', 'Part Of', $datasetSearchArray);
        $this->processDatasetRelationship($version, 'isMemberOf', 'isMemberOf', 'Member Of', $datasetSearchArray);
        $this->processDatasetRelationship($version, 'linkedDatasets', 'linkedDatasets', 'Linked Datasets', $datasetSearchArray);
    }

    /**
     * Build an array for searching dataset versions by specific fields.
     * 
     * This method gathers all dataset versions and extracts specific fields 
     * such as 'title', 'shortTitle', and 'doiName' into a searchable array. 
     * This allows for efficient dataset searching when trying to match relationships.
     *
     * @return array Array containing searchable dataset fields.
     */
    private function buildDatasetSearchArray(): array
    {
        // Retrieve all datasets from the database
        $allDatasets = Dataset::where('status',Dataset::STATUS_ACTIVE)->get(); 
        
        // Initialize an empty array to hold the dataset search data
        $datasetSearchArray = [];

        // Loop through each dataset to access its latest version
        foreach ($allDatasets as $singleDataset) {
            // Retrieve the latest version of the dataset
            $latestVersion = $singleDataset->latestVersion();

            // Only proceed if a latest version exists
            if ($latestVersion) {
                // Add the relevant metadata fields to the search array for this version
                $datasetSearchArray[] = [
                    'dataset_version_id'   => $latestVersion->id,  // The ID of the dataset version
                    'title'        => $this->getValueByPossibleKeys($latestVersion['metadata'], ['title']),  // The title of the dataset version
                    'short_title'  => $this->getValueByPossibleKeys($latestVersion['metadata'], ['shortTitle']),  // The short title, if available
                    'doi_name'     => $this->getValueByPossibleKeys($latestVersion['metadata'], ['doiName']),  // The DOI name associated with the dataset version
                ];
            }
        }

        // Return the fully built dataset search array
        return $datasetSearchArray;
    }

    /**
     * Remove existing linkages for all versions of a dataset.
     * 
     * This method clears existing linkages for the dataset versions provided in the
     * $allDatasetVersionIds array. It deletes entries from multiple tables such as 
     * DatasetVersionHasTool, PublicationHasDatasetVersion, and DatasetVersionHasDatasetVersion.
     *
     * @param array $allDatasetVersionIds Array of dataset version IDs whose linkages will be deleted.
     * @return void
     */
    private function removeExistingLinkages($allDatasetVersionIds): void
    {
        DatasetVersionHasTool::whereIn('dataset_version_id', $allDatasetVersionIds)->delete();
        PublicationHasDatasetVersion::whereIn('dataset_version_id', $allDatasetVersionIds)->delete();
        DatasetVersionHasDatasetVersion::whereIn('dataset_version_source_id', $allDatasetVersionIds)->delete();
    }

    /**
     * Process tool linkages for a specific dataset version.
     * 
     * This method searches the dataset metadata for associated tools (by URL) and 
     * links them to the dataset version. If the tool is found by its URL in the database, 
     * it creates or updates the linkage.
     *
     * @param DatasetVersion $version The dataset version for which tools are linked.
     * @return void
     */
    private function processTools($version): void
    {
        // Retrieve the 'tools' metadata field from the dataset version
        $tools = $this->getValueByPossibleKeys($version['metadata'], ['tools']);

        // Check if any tools are found in the metadata
        if ($tools) {
            // Ensure the tools variable is treated as an array (in case it's a single item)
            $tools = (array) $tools;

            // Loop through each tool URL found in the metadata
            foreach ($tools as $tool) {
                // Attempt to find a matching tool in the database by its URL
                $toolModel = Tool::where('url', $tool)->first();

                // If a matching tool is found in the database, create or update the linkage
                if ($toolModel) {
                    DatasetVersionHasTool::updateOrCreate([
                        'dataset_version_id' => $version->id,  // The current dataset version ID
                        'tool_id' => $toolModel->id,           // The ID of the matching tool
                        'link_type' => 'url matched',          // Define the type of linkage (URL match)
                    ]);

                    // Log the successful creation of the linkage
                    Log::info("Link created between datasetVersion: $version->id and tool: $tool");
                } else {
                    // Log if no matching tool is found in the database for the given URL
                    Log::info("Tool not found: $tool");
                }
            }
        } else {
            // Log if no tools were found in the dataset metadata
            Log::info("No Dataset to Tool Linkages found in metadata");
        }
    }


    /**
     * Process publication linkages for a specific dataset version.
     * 
     * This method searches the dataset metadata for publications associated with the dataset.
     * It removes any "https://doi.org/" or "http://doi.org/" from the publication DOI if present,
     * and then searches for the publication in the database by its DOI.
     *
     * @param DatasetVersion $version The dataset version for which publications are linked.
     * @param string $metadataKey The metadata key (e.g., 'publicationUsingDataset', 'publicationAboutDataset').
     * @param string $linkType The type of linkage (e.g., 'USING', 'ABOUT').
     * @return void
     */
    private function processPublications($version, $metadataKey, $linkType): void
    {
        // Retrieve the list of publications from the metadata using the provided key
        $publications = $this->getValueByPossibleKeys($version['metadata'], [$metadataKey]);

        // Check if any publications are found in the metadata
        if ($publications) {
            // Ensure the publications variable is treated as an array (in case it's a single item)
            $publications = (array) $publications;

            // Loop through each publication DOI found in the metadata
            foreach ($publications as $publication) {
                
                // Attempt to find a matching publication in the database by its DOI
                $publicationModel = Publication::where('paper_doi', $publication)->first();

                if(!$publicationModel){
                    // Remove "https://doi.org/" or "http://doi.org/" prefix from the DOI if present
                    $publication = preg_replace('/^https?:\/\/doi\.org\//', '', $publication);

                    // Try again to find a matching publication in the database by its DOI
                    $publicationModel = Publication::where('paper_doi', $publication)->first();
                }

                // If a matching publication is found, create or update the linkage
                if ($publicationModel) {
                    PublicationHasDatasetVersion::updateOrCreate([
                        'dataset_version_id' => $version->id,  // The current dataset version ID
                        'publication_id' => $publicationModel->id,  // The ID of the matching publication
                        'link_type' => $linkType,  // The type of linkage being created (e.g., 'USING', 'ABOUT')
                    ]);

                    // Log the successful creation of the linkage
                    Log::info("Link created between datasetVersion: $version->id and publication: $publication");
                } else {
                    // Log if no matching publication is found in the database for the given DOI
                    Log::info("Publication not found for DOI: $publication");
                }
            }
        } else {
            // Log if no publications were found in the dataset metadata for the given key
            Log::info("No publications found for key: $metadataKey");
        }
    }

    /**
     * Process dataset relationships such as isDerivedFrom, isPartOf, linkedDatasets, etc.
     * 
     * This method handles dataset version relationships (isDerivedFrom, isPartOf, isMemberOf, linkedDatasets, etc.)
     * by checking if the dataset linkage is a URL or an identifier, and attempts to match the dataset version.
     * If no match is found, it performs a search within dataset fields (via searchInDataset).
     *
     * @param DatasetVersion $version The dataset version being processed.
     * @param string $metadataKey The metadata key for the linkage type (e.g., 'isDerivedFrom', 'linkedDatasets').
     * @param string $linkageType The linkage type to be created.
     * @param string $linkageDescription Description of the linkage for logging purposes.
     * @param array $datasetSearchArray The search array built from dataset fields.
     * @return void
     */
    private function processDatasetRelationship($version, $metadataKey, $linkageType, $linkageDescription, $datasetSearchArray): void
    {
        // Retrieve linkage data from the metadata using the provided metadata key
        $datasetLinkages = $this->getValueByPossibleKeys($version['metadata'], [$metadataKey]);

        // Check if any linkages are found in the metadata
        if ($datasetLinkages) {
            $datasetLinkages = (array) $datasetLinkages; // Ensure it's treated as an array

            // Loop through each linkage found in the metadata
            foreach ($datasetLinkages as $datasetLinkage) {
                // If the linkage is a URL, extract the basename (e.g., last part of the URL)
                if (preg_match('/^https?:\/\//', $datasetLinkage)) {
                    $datasetLinkageBasename = basename($datasetLinkage);  // Extract the basename from the URL
                    // Try to find a dataset by ID or PID using the basename
                    $datasetModel = Dataset::where('id', $datasetLinkageBasename)
                                            ->orWhere('pid', $datasetLinkageBasename)
                                            ->first();
                } else {
                    // If it's not a URL, attempt to find the dataset directly by ID or PID
                    $datasetModel = Dataset::where('id', $datasetLinkage)
                                            ->orWhere('pid', $datasetLinkage)
                                            ->first();
                }

                $textMatches=[];
                
                // If no matching dataset model is found, perform a text-based search in dataset fields
                if (!$datasetModel) {
                    $textMatches = $this->searchInDataset($datasetSearchArray, $datasetLinkage);
                }

                // If a dataset model is found, create or update the linkage
                if ($datasetModel) {
                    // Retrieve the latest version ID of the target dataset
                    $datasetVersionTargetID = $datasetModel->versions()->latest()->first()->id;

                    // Create or update the linkage between the source and target dataset versions
                    DatasetVersionHasDatasetVersion::updateOrCreate([
                        'dataset_version_source_id' => $version->id,      // Source dataset version ID
                        'dataset_version_target_id' => $datasetVersionTargetID, // Target dataset version ID
                        'linkage_type' => $linkageType,                   // The type of linkage (e.g., 'isDerivedFrom')
                        'direct_linkage' => 1,                            // Mark the linkage as direct
                        'description' => "Linked on Dataset PID",         // Description for logging purposes
                    ]);

                    // Log the successful creation of the linkage
                    Log::info("Link created between datasetVersion: {$version->id} and datasetVersion: $datasetVersionTargetID of type: $linkageType");
                } 
                // If no dataset model was found but text matches were found, create linkages based on the text matches
                elseif ($textMatches) {
                    foreach ($textMatches as $textMatch) {
                        DatasetVersionHasDatasetVersion::updateOrCreate([
                            'dataset_version_source_id' => $version->id,                    // Source dataset version ID
                            'dataset_version_target_id' => $textMatch['dataset_version_id'],// Target dataset version ID from the search match
                            'linkage_type' => $linkageType,                                 // The type of linkage (e.g., 'isDerivedFrom')
                            'direct_linkage' => 1,                                          // Mark the linkage as direct
                            'description' => "Linked by text matching on Dataset {$textMatch['field']}",     // Describe which field matched
                        ]);

                        // Log the successful creation of the linkage based on the text match
                        Log::info("Link created between datasetVersion: {$version->id} and datasetVersion: {$textMatch['dataset_version_id']} of type: $linkageType, match={$textMatch['field']}");
                    }
                } else {
                    // Log if no matching dataset or text match was found
                    Log::info("DatasetVersion linkage not found for key: $datasetLinkage ($linkageDescription)");
                }
            }
        } else {
            // Log if no linkages were found in the metadata
            Log::info("No $linkageDescription Dataset Linkages found in metadata");
        }
    }

    /**
     * Search for a dataset linkage by any field (except version_id).
     * 
     * This method searches across multiple fields for a matching string, excluding 
     * the version_id field. It returns the version_id and the field where the match occurred.
     *
     * @param array $datasetSearchArray The array of datasets to search in.
     * @param string $searchTerm The term to search for.
     * @return array An array of matching dataset version_id and field name.
     */
    private function searchInDataset(array $datasetSearchArray, string $searchTerm): array
    {
        // Initialize an empty array to store matching results
        $matches = [];

        // Loop through each dataset version in the search array
        foreach ($datasetSearchArray as $version) {
            // Loop through each field and value within the dataset version
            foreach ($version as $field => $value) {
                // Exclude 'dataset_version_id' from the search and ensure the value is a string
                if ($field !== 'dataset_version_id' && is_string($value)) {
                    // Check if the search term exists within the field's value
                    if (strpos($value, $searchTerm) !== false) {
                        // If a match is found, add the version ID and matching field to the results array
                        $matches[] = [
                            'dataset_version_id' => $version['dataset_version_id'], // The ID of the matching dataset version
                            'field' => $field,                     // The field in which the match was found
                            'matching_value' => $value,            // The actual value that matched the search term
                        ];
                    }
                }
            }
        }

        // Return the array of matches (if any)
        return $matches;
    }
}