<?php

namespace Tests\Unit;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\TeamUserHasRoleSeeder;
use Tests\TestCase;

use App\Http\Traits\MetadataVersioning;

class MetadataRelationshipTest extends TestCase
{
    
    use MetadataVersioning;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            TeamSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            TeamHasUserSeeder::class,
            TeamUserHasRoleSeeder::class,
        ]);
    }

    // LS - Removed due to versioning needing a re-think
    //
    // public function test_that_relationships_are_copied_to_new_dataset_versions(): void
    // {
    //     $ds1 = Dataset::factory()->create();
    //     $ds2 = Dataset::factory()->create();

    //     $dsv1 = DatasetVersion::factory()->create([
    //         'dataset_id' => $ds1->id,
    //     ]);

    //     $dsv2 = DatasetVersion::factory()->create([
    //         'dataset_id' => $ds2->id,
    //     ]);

    //     $dsvhdv = DatasetVersionHasDatasetVersion::create([
    //         'dataset_version_source_id' => $dsv2->id,
    //         'dataset_version_target_id' => $dsv1->id,
    //         'linkage_type' => DatasetVersionHasDatasetVersion::LINKAGE_TYPE_DATASETS,
    //         'direct_linkage' => 1,
    //         'description' => 'Testing 123',
    //     ]);

    //     $metadata = $ds2->latestMetadata()->get();

    //     // Now add a new dataset version for dsv2
    //     $retVal = $this->addMetadataVersion(
    //         $ds2,
    //         'ACTIVE',
    //         \Carbon\Carbon::now(),
    //         $metadata[0]->metadata,
    //         $metadata[0]->metadata,
    //         $metadata[0]->version,
    //     );

    //     $copiedRelations = DatasetVersionHasDatasetVersion::where([
    //         'dataset_version_source_id' => $retVal['datasetVersionId'],
    //         'linkage_type' => DatasetVersionHasDatasetVersion::LINKAGE_TYPE_DATASETS,
    //     ])->get();

    //     $this->assertTrue(count($copiedRelations) > 0);

    //     foreach($copiedRelations as $rel) {
    //         $this->assertEquals($rel->description, $dsvhdv->description);
    //         $this->assertEquals($rel->dataset_version_source_id, ($dsv2->id + 1));
    //     }
    // }
}
