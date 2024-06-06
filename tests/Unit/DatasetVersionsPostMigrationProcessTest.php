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

class DatasetVersionsPostMigrationProcessTest extends TestCase
{
    use RefreshDatabase;

    public function testDatasetVersionHasTool()
    {
        $sector = Sector::factory()->create();
        $user = User::factory()->create(['sector_id' => $sector->id]);
        $team = Team::factory()->create();
        $team->users()->attach($user->id);
        $license = License::factory()->create();
        $category = Category::factory()->create();
        $collection = Collection::factory()->create(['team_id' => $team->id]);
        $dataset = Dataset::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
        $datasetVersion = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $tool = Tool::factory()->create([
            'user_id' => $user->id,
            'license' => $license->id,
            'category_id' => $category->id
        ]);

        $toolsPostMigrationProcess = new datasetVersionsPostMigrationProcesses();

        $datasetVersion->tools()->attach($tool);

        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool));

        $datasetVersion->tools()->detach($tool);
        $this->assertFalse($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool));
    }

    public function testDatasetVersionDoesNotHaveTool()
    {
        $sector = Sector::factory()->create();
        $user = User::factory()->create(['sector_id' => $sector->id]);
        $team = Team::factory()->create();
        $team->users()->attach($user->id);
        $license = License::factory()->create();
        $category = Category::factory()->create();
        $collection = Collection::factory()->create(['team_id' => $team->id]);
        $dataset = Dataset::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
        $datasetVersion = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $tool = Tool::factory()->create([
            'user_id' => $user->id,
            'license' => $license->id,
            'category_id' => $category->id
        ]);

        $toolsPostMigrationProcess = new datasetVersionsPostMigrationProcesses();

        $this->assertFalse($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool));
    }

    public function testDatasetVersionHasMultipleTools()
    {
        $sector = Sector::factory()->create();
        $user = User::factory()->create(['sector_id' => $sector->id]);
        $team = Team::factory()->create();
        $team->users()->attach($user->id);
        $license = License::factory()->create();
        $category = Category::factory()->create();
        $collection = Collection::factory()->create(['team_id' => $team->id]);
        $dataset = Dataset::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
        $datasetVersion = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $tool1 = Tool::factory()->create([
            'user_id' => $user->id,
            'license' => $license->id,
            'category_id' => $category->id
        ]);
        $tool2 = Tool::factory()->create([
            'user_id' => $user->id,
            'license' => $license->id,
            'category_id' => $category->id
        ]);

        $toolsPostMigrationProcess = new datasetVersionsPostMigrationProcesses();

        $datasetVersion->tools()->attach($tool1);
        $datasetVersion->tools()->attach($tool2);

        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool1));
        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool2));

        $datasetVersion->tools()->detach($tool1);
        $this->assertFalse($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool1));
        $this->assertTrue($toolsPostMigrationProcess->datasetVersionHasTool($datasetVersion, $tool2));
    } 
}
