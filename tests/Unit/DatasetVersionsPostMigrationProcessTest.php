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
use App\Models\DatasetVersionHasDatasetVersion;
use App\Console\Commands\DatasetVersionsPostMigrationProcesses;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatasetVersionsPostMigrationProcessesTest extends TestCase
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

        $toolsPostMigrationProcess = new DatasetVersionsPostMigrationProcesses();

        $datasetVersion->tools()->attach($tool);

        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasTool($datasetVersion, $tool));

        $datasetVersion->tools()->detach($tool);
        $this->assertFalse($toolsPostMigrationProcess->DatasetVersionHasTool($datasetVersion, $tool));
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

        $toolsPostMigrationProcess = new DatasetVersionsPostMigrationProcesses();

        $this->assertFalse($toolsPostMigrationProcess->DatasetVersionHasTool($datasetVersion, $tool));
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

        $toolsPostMigrationProcess = new DatasetVersionsPostMigrationProcesses();

        $datasetVersion->tools()->attach($tool1);
        $datasetVersion->tools()->attach($tool2);

        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasTool($datasetVersion, $tool1));
        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasTool($datasetVersion, $tool2));

        $datasetVersion->tools()->detach($tool1);
        $this->assertFalse($toolsPostMigrationProcess->DatasetVersionHasTool($datasetVersion, $tool1));
        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasTool($datasetVersion, $tool2));
    }

    public function testDatasetVersionHasDatasetVersion()
    {
        $sector = Sector::factory()->create();
        $user = User::factory()->create(['sector_id' => $sector->id]);
        $team = Team::factory()->create();
        $team->users()->attach($user->id);
        $collection = Collection::factory()->create(['team_id' => $team->id]);
        $dataset = Dataset::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
        $datasetVersion1 = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $datasetVersion2 = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);

        $linkage = DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $datasetVersion1->id,
            'dataset_version_target_id' => $datasetVersion2->id,
            'linkage_type' => 'some_linkage_type',
            'direct_linkage' => true,
            'description' => 'Test linkage'
        ]);

        $toolsPostMigrationProcess = new DatasetVersionsPostMigrationProcesses();

        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion2));

        $linkage->delete();
        $this->assertFalse($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion2));
    }

    public function testDatasetVersionDoesNotHaveDatasetVersion()
    {
        $sector = Sector::factory()->create();
        $user = User::factory()->create(['sector_id' => $sector->id]);
        $team = Team::factory()->create();
        $team->users()->attach($user->id);
        $collection = Collection::factory()->create(['team_id' => $team->id]);
        $dataset = Dataset::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
        $datasetVersion1 = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $datasetVersion2 = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);

        $toolsPostMigrationProcess = new DatasetVersionsPostMigrationProcesses();

        $this->assertFalse($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion2));
    }

    public function testDatasetVersionHasMultipleDatasetVersions()
    {
        $sector = Sector::factory()->create();
        $user = User::factory()->create(['sector_id' => $sector->id]);
        $team = Team::factory()->create();
        $team->users()->attach($user->id);
        $collection = Collection::factory()->create(['team_id' => $team->id]);
        $dataset = Dataset::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);
        $datasetVersion1 = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $datasetVersion2 = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);
        $datasetVersion3 = DatasetVersion::factory()->create(['dataset_id' => $dataset->id]);

        $linkage1 = DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $datasetVersion1->id,
            'dataset_version_target_id' => $datasetVersion2->id,
            'linkage_type' => 'some_linkage_type',
            'direct_linkage' => true,
            'description' => 'Test linkage 1'
        ]);

        $linkage2 = DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $datasetVersion1->id,
            'dataset_version_target_id' => $datasetVersion3->id,
            'linkage_type' => 'some_linkage_type',
            'direct_linkage' => true,
            'description' => 'Test linkage 2'
        ]);

        $toolsPostMigrationProcess = new DatasetVersionsPostMigrationProcesses();

        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion2));
        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion3));

        $linkage1->delete();
        $this->assertFalse($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion2));
        $this->assertTrue($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion3));

        $linkage2->delete();
        $this->assertFalse($toolsPostMigrationProcess->DatasetVersionHasDatasetVersion($datasetVersion1, $datasetVersion3));
    }
}
