<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dataset;
use App\Models\TeamHasUser;
use App\Models\Team;
use Tests\Traits\Authorization;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetadataManagementTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET = '/api/v1/datasets';

    protected $metadata;
    protected $metadataAlt;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            SpatialCoverageSeeder::class,
            MinimalUserSeeder::class,
        ]);

        //setup tests for non-admin
        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdminJwt,
        ];

        $this->userId =  $this->nonAdminUser['id'];

        $this->teamId = Team::all()->random()->id;
        TeamHasUser::create([
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
        ]);

        $this->nInitialActive = 3;
        $this->nInitialDraft = 1;


        $this->initialActiveIds = Dataset::factory($this->nInitialActive)->create([
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ])->pluck('id');

        $this->initialDraftIds = Dataset::factory($this->nInitialDraft)->create([
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_DRAFT,
        ])->pluck('id');

        $this->seed([DatasetVersionSeeder::class]);

    }


    public function test_can_edit_dataset_with_public_schema_as_draft(): void
    {
        $initialActiveId = $this->initialActiveIds[0];
        $initialDataset = Dataset::where("id", $initialActiveId)->first();
        $versionBeforeUpdate = $initialDataset->lastMetadataVersionNumber()->version;

        $newMetadata = $this->getPublicSchema();

        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $initialActiveId . '?elastic_indexing=0',
            [
                'team_id' => $this->teamId,
                'user_id' => $this->userId,
                'metadata' => ['metadata' => $newMetadata],
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );

        $responseUpdateDataset->assertStatus(200);

        $finalDataset = Dataset::where("id", $initialActiveId)->first();
        $versionAfterUpdate = $finalDataset->lastMetadataVersionNumber()->version;

        //when a draft, the version number shouldnt be changing
        $this->assertTrue($versionAfterUpdate === $versionBeforeUpdate);

        $latestDatasetVersion =  Dataset::find($initialActiveId)->latestVersion();
        $latestMetadata = $latestDatasetVersion['metadata'];


        #when update as a draft, the 'original_metadata' should have been filled with the new metadata
        $this->assertTrue($latestMetadata['original_metadata'] === $newMetadata);
    }

    public function test_can_edit_dataset_with_public_schema_as_active(): void
    {
        $initialActiveId = $this->initialActiveIds[0];
        $initialDataset = Dataset::where("id", $initialActiveId)->first();
        $versionBeforeUpdate = $initialDataset->lastMetadataVersionNumber()->version;
        $initialMetadata = Dataset::find($initialActiveId)->latestVersion()['metadata'];

        $newMetadata = $this->getPublicSchema();

        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $initialActiveId . '?elastic_indexing=0',
            [
                'team_id' => $this->teamId,
                'user_id' => $this->userId,
                'metadata' => $newMetadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $responseUpdateDataset->assertStatus(200);

        $finalDataset = Dataset::where("id", $initialActiveId)->first();
        $versionAfterUpdate = $finalDataset->lastMetadataVersionNumber()->version;

        //when active, the version number should have been increased
        $this->assertTrue($versionAfterUpdate === $versionBeforeUpdate);

        // LS - Removed - Calum investigating as part of another branch.
        // Not related to versioning removal.
        //
        // $latestDatasetVersion =  Dataset::find($initialActiveId)->latestVersion();
        // $latestMetadata = $latestDatasetVersion['metadata'];

        // #the revisions should have changed
        // $this->assertFalse($initialMetadata['metadata']['required']['revisions'] === $latestMetadata['metadata']['required']['revisions']);
    }

}
