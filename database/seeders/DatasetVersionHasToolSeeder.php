<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasTool;
use App\Models\Tool;

class DatasetVersionHasToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($count = 1; $count <= 10; $count++) {
            $datasetVersionId = DatasetVersion::all()->random()->id;
            $toolId = Tool::all()->random()->id;

            $DatasetVersionHasTool = DatasetVersionHasTool::where([
                'dataset_version_id' => $datasetVersionId,
                'tool_id' => $toolId,
            ])->first();

            if (!$DatasetVersionHasTool) {
                DatasetVersionHasTool::create([
                    'dataset_version_id' => $datasetVersionId,
                    'tool_id' => $toolId,
                ]);
            }
        }
    }
}
