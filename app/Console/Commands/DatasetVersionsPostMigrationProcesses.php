<?php

namespace App\Console\Commands;

use App\Models\DatasetVersion;
use App\Models\Tool;

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
}
