<?php

namespace App\Http\Traits;

use App\Models\Dataset;
use App\Models\DatasetVersion;

trait GetDatasetViaDatasetVersions
{
    /**
     * Get all datasets associated with the latest versions.
     */
    public function getDatasetsViaDatasetVersion($linkageTable, $localTableId)
    {
        // Step 1: Get the dataset version IDs from the linkage table
        $versionIds = $linkageTable::where($localTableId, $this->id)->pluck('dataset_version_id')->toArray();
        
        // Step 2: Use the version IDs to find all related dataset IDs through the linkage table
        $datasetIds = DatasetVersion::whereIn('id', $versionIds)
            ->pluck('dataset_id')
            ->unique()
            ->toArray();

        // Step 3: Retrieve all datasets using the collected dataset IDs
        $datasets = Dataset::whereIn('id', $datasetIds)->get();

        // Initialize an array to store transformed datasets
        $transformedDatasets = [];

        // Iterate through each dataset and add associated dataset versions
        foreach ($datasets as $dataset) {
            // Retrieve dataset version IDs associated with the current dataset
            $datasetVersionIds = $dataset->versions()->whereIn('id', $versionIds)->pluck('id')->toArray();

            // Add associated dataset versions to the dataset object
            $dataset->dataset_version_ids = $datasetVersionIds;

            // Add the enhanced dataset to the transformed datasets array
            $transformedDatasets[] = $dataset;
        }

        // Return the array of transformed datasets
        return $transformedDatasets;
    }
}
