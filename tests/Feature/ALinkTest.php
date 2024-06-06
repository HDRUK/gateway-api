<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tool;
use App\Models\DatasetVersion;
use App\Models\Dataset;
use App\Models\DatasetVersionHasTool;
use App\Console\Commands\ToolsPostMigrationProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    public function testDatasetVersionHasTool()
    {
        $dataset = Dataset::factory()->create();
        $datasetVersion = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $tool = Tool::factory()->create();

        $toolsPostMigrationProcess = new ToolsPostMigrationProcess();

        $datasetVersion->tools()->attach($tool);

        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool));

        $datasetVersion->tools()->detach($tool);
        $this->assertFalse($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool));
    }

    public function testDatasetVersionDoesNotHaveTool()
    {
        $dataset = Dataset::factory()->create();
        $datasetVersion = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $tool = Tool::factory()->create();

        $toolsPostMigrationProcess = new ToolsPostMigrationProcess();

        $this->assertFalse($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool));
    }

    public function testDatasetVersionHasMultipleTools()
    {
        $dataset = Dataset::factory()->create();
        $datasetVersion = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $tool1 = Tool::factory()->create();
        $tool2 = Tool::factory()->create();

        $toolsPostMigrationProcess = new ToolsPostMigrationProcess();

        $datasetVersion->tools()->attach($tool1);
        $datasetVersion->tools()->attach($tool2);

        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool1));
        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool2));

        $datasetVersion->tools()->detach($tool1);
        $this->assertFalse($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool1));
        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool2));
    }
}
