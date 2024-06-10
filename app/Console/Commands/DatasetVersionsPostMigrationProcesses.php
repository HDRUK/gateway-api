<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\Tool;
use App\Models\DatasetVersionHasDatasetVersion;

class DatasetVersionsPostMigrationProcesses
{
    /**
     * Check if a dataset version has a specific tool.
     *
     * @param DatasetVersion $datasetVersion
     * @param Tool $tool
     * @return bool
     */
    public function datasetVersionHasTool(DatasetVersion $datasetVersion, Tool $tool): bool
    {
        return $datasetVersion->tools()->where('tool_id', $tool->id)->exists();
    }

    /**
     * Check if a dataset version has a specific dataset version linkage.
     *
     * @param DatasetVersion $datasetVersion1
     * @param DatasetVersion $datasetVersion2
     * @return bool
     */
    public function datasetVersionHasDatasetVersion(DatasetVersion $datasetVersion1, DatasetVersion $datasetVersion2): bool
    {
        return DatasetVersionHasDatasetVersion::where('dataset_version_source_id', $datasetVersion1->id)
            ->where('dataset_version_target_id', $datasetVersion2->id)
            ->exists();
    }
}
