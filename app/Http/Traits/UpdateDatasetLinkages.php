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
        $tools = $this->getValueByPossibleKeys($version['metadata'], ['tools']);

        if ($tools) {

            $tools =  array($tools);

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

        // 
        // PROCESS PUBLICATIONS USING DATASETS
        //
        $publicationsUsingDataset = $this->getValueByPossibleKeys($version['metadata'], ['publicationUsingDataset']);

        if ($publicationsUsingDataset) {

            $publicationsUsingDataset =  array($publicationsUsingDataset);

            foreach ($publicationsUsingDataset as $publicationUsing) {
                $publicationUsingModel = Publication::where('paper_doi', $publicationUsing)->first();

                if ($publicationUsingModel) {
                    PublicationHasDatasetVersion::updateOrCreate([
                        'dataset_version_id' => $version->id,
                        'publication_id' => $publicationUsingModel->id,
                        'link_type' => "USING",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and publication: $publicationUsing");
                } else {
                    Log::info("Publication not found for DOI: $publicationUsing");
                }
            }
        } else {
            Log::info("No Publication about the dataset found in metadata");
        }

        // 
        // PROCESS PUBLICATIONS ABOUT DATASETS
        //
        $publicationsAboutDataset = $this->getValueByPossibleKeys($version['metadata'], ['publicationAboutDataset']);

        if ($publicationsAboutDataset) {

            $publicationsAboutDataset =  array($publicationsAboutDataset);

            foreach ($publicationsAboutDataset as $publicationAbout) {
                $publicationAboutModel = Publication::where('paper_doi', $publicationAbout)->first();

                if ($publicationAboutModel) {
                    PublicationHasDatasetVersion::updateOrCreate([
                        'dataset_version_id' => $version->id,
                        'publication_id' => $publicationAboutModel->id,
                        'link_type' => "ABOUT",
                    ]);
                    Log::info("Link created between datasetVersion: $version->id and publication: $publicationAbout");
                } else {
                    Log::info("Publication not found for DOI: $publicationAbout");
                }
            }
        } else {
            Log::info("No Publication about the dataset found in metadata");
        }

        // 
        // PROCESS DATASET VERSION IS DERIVED FROM DATASET VERSION
        //

        $datasetIsDerivedFrom = $this->getValueByPossibleKeys($version['metadata'], ['isDerivedFrom']);

        if ($datasetIsDerivedFrom) {

            $datasetIsDerivedFrom =  array($datasetIsDerivedFrom);

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
        $datasetIsPartOf = $this->getValueByPossibleKeys($version['metadata'], ['isPartOf']);

        if ($datasetIsPartOf) {

            $datasetIsPartOf =  array($datasetIsPartOf);

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
        $datasetIsMemberOf = $this->getValueByPossibleKeys($version['metadata'], ['isMemberOf']);

        if ($datasetIsMemberOf) {

            $datasetIsMemberOf =  array($datasetIsMemberOf);

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
        $datasetLinkedDatasets = $this->getValueByPossibleKeys($version['metadata'], ['linkedDatasets']);

        if ($datasetLinkedDatasets) {

            $datasetLinkedDatasets =  array($datasetLinkedDatasets);

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