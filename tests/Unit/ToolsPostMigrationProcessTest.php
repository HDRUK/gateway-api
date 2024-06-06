<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tool;
use App\Models\Sector;
use App\Models\License;
use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use App\Models\Collection;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\ProgrammingLanguage;
use App\Models\ProgrammingPackage;
use App\Models\ToolHasProgrammingLanguage;
use App\Models\ToolHasTypeCategory;
use App\Models\TypeCategory;
use App\Console\Commands\ToolsPostMigrationProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ToolsPostMigrationProcessTest extends TestCase
{
    use RefreshDatabase;

    public function testReadMigrationFile()
    {
        $toolsPostMigrationProcess = new ToolsPostMigrationProcess();
        $migrationFile = storage_path('migration_files/tool_metadata_export_mapping.csv');

        // Create a sample CSV file for testing
        $sampleData = [
            ['_id', 'License MK2', 'MK2 Programming Language (array)', 'MK2 Type_Category (array)'],
            ['1', 'License1', 'Python, Java', 'Category1, Category2'],
            ['2', 'License2', 'PHP, Ruby', 'Category3, Category4'],
        ];
        
        if (!file_exists(storage_path('migration_files'))) {
            mkdir(storage_path('migration_files'), 0755, true);
        }

        $file = fopen($migrationFile, 'w');
        foreach ($sampleData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        $toolsPostMigrationProcess->handle();

        // Assert the CSV data was read correctly
        $csvData = $toolsPostMigrationProcess->getCsvData();
        $this->assertCount(2, $csvData);
        $this->assertEquals('1', $csvData[0]['_id']);
        $this->assertEquals('License1', $csvData[0]['License MK2']);
        $this->assertEquals('Python, Java', $csvData[0]['MK2 Programming Language (array)']);
        $this->assertEquals('Category1, Category2', $csvData[0]['MK2 Type_Category (array)']);
    }

    public function testHandleMethod()
    {
        // Setup for testing the handle method
        $toolsPostMigrationProcess = new ToolsPostMigrationProcess();

        // Create necessary models and relationships
        $user = User::factory()->create();
        $license = License::factory()->create(['code' => 'License1']);
        $programmingLanguage1 = ProgrammingLanguage::factory()->create(['name' => 'Python']);
        $programmingLanguage2 = ProgrammingLanguage::factory()->create(['name' => 'Java']);
        $typeCategory1 = TypeCategory::factory()->create(['name' => 'Category1']);
        $typeCategory2 = TypeCategory::factory()->create(['name' => 'Category2']);
        $tool = Tool::factory()->create([
            'mongo_object_id' => '1',
            'user_id' => $user->id,
            'license' => $license->id,
            'category_id' => $typeCategory1->id,
        ]);

        // Create a sample CSV file for testing
        $sampleData = [
            ['_id', 'License MK2', 'MK2 Programming Language (array)', 'MK2 Type_Category (array)'],
            ['1', 'License1', 'Python, Java', 'Category1, Category2'],
        ];
        
        $migrationFile = storage_path('migration_files/tool_metadata_export_mapping.csv');

        if (!file_exists(storage_path('migration_files'))) {
            mkdir(storage_path('migration_files'), 0755, true);
        }

        $file = fopen($migrationFile, 'w');
        foreach ($sampleData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Execute the handle method
        $toolsPostMigrationProcess->handle();

        // Assert that the tool has the expected relationships and data
        $this->assertDatabaseHas('tool_has_programming_language', [
            'tool_id' => $tool->id,
            'programming_language_id' => $programmingLanguage1->id,
        ]);

        $this->assertDatabaseHas('tool_has_programming_language', [
            'tool_id' => $tool->id,
            'programming_language_id' => $programmingLanguage2->id,
        ]);

        $this->assertDatabaseHas('tool_has_type_category', [
            'tool_id' => $tool->id,
            'type_category_id' => $typeCategory1->id,
        ]);

        $this->assertDatabaseHas('tool_has_type_category', [
            'tool_id' => $tool->id,
            'type_category_id' => $typeCategory2->id,
        ]);
    }


    public function testIndexElasticTool()
    {
        // Setup for testing the indexElasticTool method
        $toolsPostMigrationProcess = new ToolsPostMigrationProcess();

        // Create necessary models and relationships
        $user = User::factory()->create();
        $license = License::factory()->create(['code' => 'License1']);
        $programmingLanguage1 = ProgrammingLanguage::factory()->create(['name' => 'Python']);
        $programmingLanguage2 = ProgrammingLanguage::factory()->create(['name' => 'Java']);
        $typeCategory1 = TypeCategory::factory()->create(['name' => 'Category1']);
        $typeCategory2 = TypeCategory::factory()->create(['name' => 'Category2']);
        $tool = Tool::factory()->create([
            'mongo_object_id' => '1',
            'user_id' => $user->id,
            'license' => $license->id,
            'category_id' => $typeCategory1->id,
        ]);
        $typeCategory = TypeCategory::factory()->create();
        $programmingLanguage = ProgrammingLanguage::factory()->create();
        $programmingPackage = ProgrammingPackage::factory()->create();

        ToolHasProgrammingLanguage::create([
            'tool_id' => $tool->id,
            'programming_language_id' => $programmingLanguage->id,
        ]);

        ToolHasTypeCategory::create([
            'tool_id' => $tool->id,
            'type_category_id' => $typeCategory->id,
        ]);

        // Call the indexElasticTool method
        $toolsPostMigrationProcess->indexElasticTool($tool->id);

        // Assert that the tool was indexed in Elasticsearch (assuming mock or testing setup for Elasticsearch)
        // This part will depend on how you have Elasticsearch set up for testing.
    }
}
