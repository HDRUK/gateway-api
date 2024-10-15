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
     * Add SQL linkages based on GWDM2.0 object
     * 
     * This function processes and creates SQL linkages for tools, publications, 
     * and dataset relationships (isDerivedFrom, isPartOf, etc.) for a given dataset 
     * using the latest metadata version.
     *
     * @param Dataset $dataset The dataset object for which linkages are created.
     * @param bool $delete Whether to delete existing linkages.
     * @return void
     */
    public function createSqlLinkage(Dataset $dataset, bool $delete = false): void
    {
        // Retrieve the latest dataset_version for the given dataset_id
        $version = $dataset->latestVersion();

        // Retrieve all dataset_version_ids for the given dataset_id
        $allDatasetVersionIds = DatasetVersion::where('dataset_id', $dataset->id)->pluck('id');

        // Build the dataset search array
        $datasetSearchArray = $this->buildDatasetSearchArray();

        // Remove existing linkages if delete is set to true
        if ($delete) {
            $this->removeExistingLinkages($allDatasetVersionIds);
        }

        Log::info("Processing Dataset: $dataset->id");
                
        // Process tools, publications, and dataset linkages
        $this->processTools($version);
        $this->processPublications($version, 'publicationUsingDataset', 'USING');
        $this->processPublications($version, 'publicationAboutDataset', 'ABOUT');

        // Process dataset relationships
        $this->processDatasetRelationship($version, 'isDerivedFrom', 'isDerivedFrom', 'Derived From', $datasetSearchArray);
        $this->processDatasetRelationship($version, 'isPartOf', 'isPartOf', 'Part Of', $datasetSearchArray);
        $this->processDatasetRelationship($version, 'isMemberOf', 'isMemberOf', 'Member Of', $datasetSearchArray);
        $this->processDatasetRelationship($version, 'linkedDatasets', 'linkedDatasets', 'Linked Datasets', $datasetSearchArray);
    }

    /**
     * Build search array for dataset versions
     * 
     * Note: Tom Giles 15th Oct 2024
     * Ive done this so that I only have to call all of the datasetVersion objects once.
     * This can be expanded if we want to search for other fields in the future
     */
    private function buildDatasetSearchArray(): array
    {
        $allDatasets = Dataset::all(); 
        $datasetSearchArray = [];

        foreach ($allDatasets as $singleDataset) {
            $latestVersion = $singleDataset->latestVersion();

            if ($latestVersion) {
                $datasetSearchArray[] = [
                    'version_id'   => $latestVersion->id,
                    'title'        => $this->getValueByPossibleKeys($latestVersion['metadata'], ['summary.title']),
                    'short_title'  => $this->getValueByPossibleKeys($latestVersion['metadata'], ['summary.shortTitle']),
                    'doi_name'     => $this->getValueByPossibleKeys($latestVersion['metadata'], ['doiName']),
                ];
            }
        }

        return $datasetSearchArray;
    }

    /**
     * Remove existing linkages if delete flag is true
     * 
     * Note Tom Giles 15th Oct 2024
     * 
     * This gives us the option of clearing the linkage for all other dataset Versions
     * when a new dataset version is added. Not sure if this should be default behaviour 
     * for now untill the use of dataset versions is properly established? 
     */
    private function removeExistingLinkages($allDatasetVersionIds): void
    {
        DatasetVersionHasTool::whereIn('dataset_version_id', $allDatasetVersionIds)->delete();
        PublicationHasDatasetVersion::whereIn('dataset_version_id', $allDatasetVersionIds)->delete();
        DatasetVersionHasDatasetVersion::whereIn('dataset_version_source_id', $allDatasetVersionIds)->delete();
    }

    /**
     * Process tools linkages for the dataset version
     * 
     * Note Tom Giles 15th Oct 2024
     * 
     * Individual assesor for tools. currently matches on URL only
     */
    private function processTools($version): void
    {
        $tools = $this->getValueByPossibleKeys($version['metadata'], ['tools']);

        if ($tools) {
            $tools = (array) $tools; // Ensure it's an array

            foreach ($tools as $tool) {
                $toolModel = Tool::where('url', $tool)->first();

                if ($toolModel) {
                    DatasetVersionHasTool::updateOrCreate([
                        'dataset_version_id' => $version->id,
                        'tool_id' => $toolModel->id,
                        'link_type' => 'url matched',
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and tool: $tool");
                } else {
                    Log::info("Tool not found: $tool");
                }
            }
        } else {
            Log::info("No Dataset to Tool Linkages found in metadata");
        }
    }

    /**
     * Process publications linked to the dataset version
     * 
     * * Note Tom Giles 15th Oct 2024
     * 
     * Individual assesor for publications. currently matches on doi only
     * I have made it smart enough to remove "https://doi.org/" or "http://doi.org/" 
     * from the start of the string if present.
     */
    private function processPublications($version, $metadataKey, $linkType): void
    {
        $publications = $this->getValueByPossibleKeys($version['metadata'], [$metadataKey]);

        if ($publications) {
            $publications = (array) $publications; // Ensure it's an array

            foreach ($publications as $publication) {

                // Remove "https://doi.org/" or "http://doi.org/" from the start of the string if present
                $publication = preg_replace('/^https?:\/\/doi\.org\//', '', $publication);


                $publicationModel = Publication::where('paper_doi', $publication)->first();

                if ($publicationModel) {
                    PublicationHasDatasetVersion::updateOrCreate([
                        'dataset_version_id' => $version->id,
                        'publication_id' => $publicationModel->id,
                        'link_type' => $linkType,
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and publication: $publication");
                } else {
                    Log::info("Publication not found for DOI: $publication");
                }
            }
        } else {
            Log::info("No publications found for key: $metadataKey");
        }
    }

    /**
     * Process dataset version linked to the dataset version 
     * (isDerivedFrom, isPartOf, isMemberOf, linkedDatasets, etc)
     * 
     * * * Note Tom Giles 15th Oct 2024
     * 
     * Individual assesor for Dataset Versions linkages. 
     * 
     * I have made it smart enough to check if it is a url. if it is then get the basename
     * check if that matches either a datasetID or datasetPID, if its not a url, perform then
     * check if that string matches either a datasetID or datasetPID.
     * 
     * If there still is not a match then we use the searchInDataset() function to look for hits
     * this trys to create linkage matches on the fields as defined within the 
     * buildDatasetSearchArray() function.  
     */
    
    private function processDatasetRelationship($version, $metadataKey, $linkageType, $linkageDescription, $datasetSearchArray): void
    {
        // Retrieve linkage information from the metadata
        $datasetLinkages = $this->getValueByPossibleKeys($version['metadata'], [$metadataKey]);

        if ($datasetLinkages) {
            // Ensure linkages are treated as an array
            $datasetLinkages = (array) $datasetLinkages;

            foreach ($datasetLinkages as $datasetLinkage) {
                // Check if the string starts with http:// or https://
                if (preg_match('/^https?:\/\//', $datasetLinkage)) {
                    // Apply basename() only if it's a URL
                    $datasetLinkageBasename = basename($datasetLinkage);
                    // Try to find the dataset model by its DatasetID
                    $datasetModel = Dataset::where('id', $datasetLinkageBasename)->first();
                    
                    if (!$datasetModel) {
                        // Try to find the dataset model by its DatasetPID
                        $datasetModel = Dataset::where('pid', $datasetLinkageBasename)->first();
                    }
                } else {
                    // Try to find the dataset model by its DatasetID
                    $datasetModel = Dataset::where('id', $datasetLinkage)->first();
                    
                    if (!$datasetModel) {
                        // Try to find the dataset model by its DatasetPID
                        $datasetModel = Dataset::where('pid', $datasetLinkage)->first();
                    }
                }
                
                if (!$datasetModel) {
                    // Perform a text-based search if no dataset model is found
                    $textMatches = $this->searchInDataset($datasetSearchArray, $datasetLinkage);
                }

                if ($datasetModel) {
                    // Link to the dataset version by PID
                    $datasetVersionTargetID = $datasetModel->versions()->latest()->first()->id;
                    DatasetVersionHasDatasetVersion::updateOrCreate([
                        'dataset_version_source_id' => $version->id,
                        'dataset_version_target_id' => $datasetVersionTargetID,
                        'linkage_type' => $linkageType,
                        'direct_linkage' => 1,
                        'description' => "Linked on Dataset PID",
                    ]);
                    Log::info("Link created between datasetVersion: {$version->id} and datasetVersion: $datasetVersionTargetID of type: $linkageType");
                } elseif ($textMatches) {
                    // Link based on the text matches
                    foreach ($textMatches as $textMatch) {
                        $matchField = $textMatch['field'];
                        DatasetVersionHasDatasetVersion::updateOrCreate([
                            'dataset_version_source_id' => $version->id,
                            'dataset_version_target_id' => $textMatch['version_id'],
                            'linkage_type' => $linkageType,
                            'direct_linkage' => 1,
                            'description' => "Linked on Dataset $matchField",
                        ]);
                        Log::info("Link created between datasetVersion: {$version->id} and datasetVersion: {$textMatch['version_id']} of type: $linkageType, match=$matchField");
                    }
                } else {
                    // Log if no matching dataset or text was found
                    Log::info("DatasetVersion linkage not found for key: $datasetLinkage ($linkageDescription)");
                }
            }
        } else {
            Log::info("No $linkageDescription Dataset Linkages found in metadata");
        }
    }

    /**
     * Search by any field (except version_id) and return the version_id and matching field name
     */
    private function searchInDataset(array $datasetSearchArray, string $searchTerm): array
    {
        $matches = [];

        foreach ($datasetSearchArray as $version) {
            foreach ($version as $field => $value) {
                if ($field !== 'version_id' && is_string($value) && strpos($value, $searchTerm) !== false) {
                    $matches[] = [
                        'version_id' => $version['version_id'],
                        'field' => $field,
                        'matching_value' => $value,
                    ];
                }
            }
        }

        return $matches;
    }
}