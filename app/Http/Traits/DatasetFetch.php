<?php

namespace App\Http\Traits;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\PublicationHasDatasetVersion;

trait DatasetFetch
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

        // Iterate through each dataset and add associated dataset versions
        foreach ($datasets as $dataset) {
            // Retrieve dataset version IDs associated with the current dataset
            $datasetVersionIds = $dataset->versions()->whereIn('id', $versionIds)->pluck('id')->toArray();
            $datasetMetadata = $dataset->latestMetadata()->whereIn('id', $versionIds)->pluck('metadata')->toArray(); // This can be modified to return metadata

            if (count($datasetMetadata) > 0) {
                $datasetTitle = $datasetMetadata[0]['metadata']['summary']['title'] ?? null;
                $dataset->setAttribute('name', $datasetTitle); // This can be modified to return metadata
            }
            // Add associated dataset versions to the dataset object
            $dataset->setAttribute('dataset_version_ids', $datasetVersionIds);
            // Add extra fields as required for DatasetVersionHasTool case.
            if ($linkageTable instanceof DatasetVersionHasTool) {
                $link_type = DatasetVersionHasTool::where($localTableId, $this->id)->whereIn('dataset_version_id', $datasetVersionIds)->select(['link_type'])->first();
                $dataset->setAttribute('link_type', $link_type['link_type']);
                $metadata = $dataset->lastMetadata();
                $dataset->setAttribute('title', $metadata["metadata"]["summary"]["title"]);
            } elseif ($linkageTable instanceof PublicationHasDatasetVersion) {
                $link_type = PublicationHasDatasetVersion::where($localTableId, $this->id)->whereIn('dataset_version_id', $datasetVersionIds)->select(['link_type'])->first();
                $dataset->setAttribute('link_type', $link_type['link_type']);
            }
        }

        // Return the collection of datasets with injected dataset version IDs
        return $datasets->toArray();
    }
}
