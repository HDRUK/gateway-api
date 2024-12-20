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

trait UpdateDatasetLinkages
{

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

        // 
        // REMOVE OLD LINKAGES
        // 
        if ($delete == true) {
            // Delete all links for all DatasetVersions (clear the linkage history)
            DatasetVersionHasTool::whereIn('dataset_version_id', $allDatasetVersionIds)->delete();
            PublicationHasDatasetVersion::whereIn('dataset_version_id', $allDatasetVersionIds)->delete();
            DatasetVersionHasDatasetVersion::whereIn('dataset_version_source_id', $allDatasetVersionIds)->delete();
        }
        
        // 
        // PROCESS TOOLS
        // 
        if (isset($version['metadata']['metadata']['linkage']['tools'])) {

            $tools = (array) $version['metadata']['metadata']['linkage']['tools'];

            foreach ($tools as $tool) {
                if (filter_var($tool, FILTER_VALIDATE_URL) == true) {
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
                } else {
                    Log::warning("Invalid URL found: $tool");
                }
            }
        } else {
            Log::info("No Dataset to Tool Linkages found in metadata");
        }

        // 
        // PROCESS PUBLICATIONS USING DATASETS
        //
        if (isset($version['metadata']['metadata']['linkage']['publicationUsingDataset'])) {
        
            $publicationsUsingDataset = (array) $version['metadata']['metadata']['linkage']['publicationUsingDataset'];

            foreach ($publicationsUsingDataset as $publication) {
                $publicationModel = Publication::where('paper_doi', $publication)->first();

                if ($publicationModel) {
                    PublicationHasDatasetVersion::updateOrCreate([
                        'dataset_version_id' => $version->id,
                        'publication_id' => $publicationModel->id,
                        'link_type' => "Used on",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and publication: $publication");
                } else {
                    Log::info("Publication not found for DOI: $publication");
                }
            }
        } else {
            Log::info("No Publication using the Dataset Linkages found in metadata");
        }

        // 
        // PROCESS PUBLICATIONS ABOUT DATASETS
        //
        if (isset($version['metadata']['metadata']['linkage']['publicationAboutDataset'])) {
            
            $publicationsAboutDataset = (array) $version['metadata']['metadata']['linkage']['publicationAboutDataset'];

            foreach ($publicationsAboutDataset as $publication) {
                $publicationModel = Publication::where('paper_doi', $publication)->first();

                if ($publicationModel) {
                    PublicationHasDatasetVersion::updateOrCreate([
                        'dataset_version_id' => $version->id,
                        'publication_id' => $publicationModel->id,
                        'link_type' => "ABOUT",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and publication: $publication");
                } else {
                    Log::info("Publication not found for DOI: $publication");
                }
            }
        } else {
            Log::info("No Publication about the dataset found in metadata");
        }

        // 
        // PROCESS DATASET VERSION IS DERIVED FROM DATASET VERSION
        //
        if (isset($version['metadata']['metadata']['linkage']['datasetLinkage']['isDerivedFrom'])) {
        
            $datasetIsDerivedFrom = (array) $version['metadata']['metadata']['linkage']['datasetLinkage']['isDerivedFrom'];

            foreach ($datasetIsDerivedFrom as $datasetLinkage) {
                $datasetLinkage = basename($datasetLinkage);
                $datasetModel = Dataset::where('pid', $datasetLinkage)->first();
                    
                if ($datasetModel) {
                    $datasetVersionTargetID = $datasetModel->versions()->latest()->first()->id;
                    DatasetVersionHasDatasetVersion::updateOrCreate([
                        'dataset_version_source_id' => $version->id,
                        'dataset_version_target_id' => $datasetVersionTargetID,
                        'linkage_type' => "isDerivedFrom",
                        'direct_linkage' =>  1,
                        'description' => "Linked on Dataset ID",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and datasetVersion: $datasetVersionTargetID of type: isDerivedFrom");
                } else {
                    Log::info("datasetVersion not found for key: $datasetLinkage");
                }
            }
        } else {
            Log::info("No Is Derived From Dataset Linkages found in metadata");
        }

        // 
        // PROCESS DATASET VERSION IS PART OF DATASET VERSION
        //
        if (isset($version['metadata']['metadata']['linkage']['datasetLinkage']['isPartOf'])) {
        
            $datasetIsPartOf = (array) $version['metadata']['metadata']['linkage']['datasetLinkage']['isPartOf'];

            foreach ($datasetIsPartOf as $datasetLinkage) {
                $datasetLinkage = basename($datasetLinkage);
                $datasetModel = Dataset::where('pid', $datasetLinkage)->first();
                    
                if ($datasetModel) {
                    $datasetVersionTargetID = $datasetModel->versions()->latest()->first()->id;
                    DatasetVersionHasDatasetVersion::updateOrCreate([
                        'dataset_version_source_id' => $version->id,
                        'dataset_version_target_id' => $datasetVersionTargetID,
                        'linkage_type' => "isPartOf",
                        'direct_linkage' =>  1,
                        'description' => "Linked on Dataset ID",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and datasetVersion: $datasetVersionTargetID of type: isPartOf");
                } else {
                    Log::info("datasetVersion not found for key: $datasetLinkage");
                }
            }
        } else {
            Log::info("No Is Part Of Dataset Linkages found in metadata");
        }

        // 
        // PROCESS DATASET VERSION IS MEMBER OF DATASET VERSION
        //
        if (isset($version['metadata']['metadata']['linkage']['datasetLinkage']['isMemberOf'])) {
        
            $datasetIsMemberOf = (array) $version['metadata']['metadata']['linkage']['datasetLinkage']['isMemberOf'];

            foreach ($datasetIsMemberOf as $datasetLinkage) {
                $datasetLinkage = basename($datasetLinkage);
                $datasetModel = Dataset::where('pid', $datasetLinkage)->first();
                    
                if ($datasetModel) {
                    $datasetVersionTargetID = $datasetModel->versions()->latest()->first()->id;
                    DatasetVersionHasDatasetVersion::updateOrCreate([
                        'dataset_version_source_id' => $version->id,
                        'dataset_version_target_id' => $datasetVersionTargetID,
                        'linkage_type' => "isMemberOf",
                        'direct_linkage' =>  1,
                        'description' => "Linked on Dataset ID",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and datasetVersion: $datasetVersionTargetID of type: isMemberOf");
                } else {
                    Log::info("datasetVersion not found for key: $datasetLinkage");
                }
            }
        } else {
            Log::info("No Is Member Of Dataset Linkages found in metadata");
        }

        // 
        // PROCESS DATASET VERSION IS LINKED TO ANOTHER DATASET VERSION
        //
        if (isset($version['metadata']['metadata']['linkage']['datasetLinkage']['linkedDatasets'])) {
        
            $datasetLinkedDatasets = (array) $version['metadata']['metadata']['linkage']['datasetLinkage']['linkedDatasets'];

            foreach ($datasetLinkedDatasets as $datasetLinkage) {
                $datasetLinkage = basename($datasetLinkage);
                $datasetModel = Dataset::where('pid', $datasetLinkage)->first();
                    
                if ($datasetModel) {
                    $datasetVersionTargetID = $datasetModel->versions()->latest()->first()->id;
                    DatasetVersionHasDatasetVersion::updateOrCreate([
                        'dataset_version_source_id' => $version->id,
                        'dataset_version_target_id' => $datasetVersionTargetID,
                        'linkage_type' => "linkedDatasets",
                        'direct_linkage' =>  1,
                        'description' => "Linked on Dataset ID",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and datasetVersion: $datasetVersionTargetID of type: linkedDatasets");
                } else {
                    Log::info("datasetVersion not found for key: $datasetLinkage");
                }
            }
        } else {
            Log::info("No Linked Datasets Dataset Linkages found in metadata");
        }
    }
}