<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\Team;
use App\Models\User;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use App\Http\Enums\TeamMemberOf;
use Database\Seeders\TeamSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\MinimalUserSeeder;

class DatasetVersionTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET = '/api/v1/datasets';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';

    public $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            SpatialCoverageSeeder::class,
            TeamSeeder::class,
            UserSeeder::class
        ]);

        $this->metadata = $this->getMetadata();
    }

    public function test_a_dataset_version_is_created_on_new_dataset_created(): void
    {
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );

        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Test Team 1',
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'users' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId = $contentCreateTeam['data'];

        // create user
        $responseCreateUser = $this->json(
            'POST',
            self::TEST_URL_USER,
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => 'firstname.lastname.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => " https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234566,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header,
        );
        $responseCreateUser->assertStatus(201);
        $contentCreateUser = $responseCreateUser->decodeResponseJson();
        $userId = $contentCreateUser['data'];

        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $responseCreateDataset->assertStatus(201);
        $datasetId = $responseCreateDataset['data'];

        $version = DatasetVersion::where('dataset_id', $datasetId)->get();
        $this->assertTrue((count($version)) === 1);

        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );

        $responseDeleteDataset->assertStatus(200);

        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );

        $responseDeleteTeam->assertStatus(200);

        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );

        $responseDeleteUser->assertStatus(200);
    }

    public function test_dataset_metadata_publisher_is_saved_correctly(): void
    {
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );

        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Test Team 1',
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'users' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId = $contentCreateTeam['data'];

        // create user
        $responseCreateUser = $this->json(
            'POST',
            self::TEST_URL_USER,
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => 'firstname.lastname.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => " https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234566,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header,
        );
        $responseCreateUser->assertStatus(201);
        $contentCreateUser = $responseCreateUser->decodeResponseJson();
        $userId = $contentCreateUser['data'];

        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );

        $responseCreateDataset->assertStatus(201);
        $datasetId = $responseCreateDataset['data'];
        $dataset = Dataset::with('versions')->where('id', $datasetId)->first();

        $metadata = $dataset->versions[0]->metadata;

        $teamPid = Team::where('id', $teamId)->first()->getPid();

        $publisherId = $metadata['metadata']['summary']['publisher'];
        if (version_compare(Config::get('metadata.GWDM.version'), "1.1", "<")) {
            $publisherId =  $publisherId['publisherId'];
            $this->assertEquals(
                $publisherId,
                $teamPid
            );
        }

    }

    // LS - Removed due to removing versioning for a time
    //
    // public function test_a_dataset_version_is_created_on_dataset_update(): void
    // {
    //     // First create a notification to be used by the new team
    //     $responseNotification = $this->json(
    //         'POST',
    //         self::TEST_URL_NOTIFICATION,
    //         [
    //             'notification_type' => 'applicationSubmitted',
    //             'message' => 'Some message here',
    //             'email' => null,
    //             'user_id' => 3,
    //             'opt_in' => 1,
    //             'enabled' => 1,
    //         ],
    //         $this->header,
    //     );

    //     $contentNotification = $responseNotification->decodeResponseJson();
    //     $notificationID = $contentNotification['data'];

    //     $responseCreateTeam = $this->json(
    //         'POST',
    //         self::TEST_URL_TEAM,
    //         [
    //             'name' => 'Test Team 1',
    //             'enabled' => 1,
    //             'allows_messaging' => 1,
    //             'workflow_enabled' => 1,
    //             'access_requests_management' => 1,
    //             'uses_5_safes' => 1,
    //             'is_admin' => 1,
    //             'member_of' => fake()->randomElement([
    //                 TeamMemberOf::ALLIANCE,
    //                 TeamMemberOf::HUB,
    //                 TeamMemberOf::OTHER,
    //                 TeamMemberOf::NCS,
    //             ]),
    //             'contact_point' => 'dinos345@mail.com',
    //             'application_form_updated_by' => 'Someone Somewhere',
    //             'application_form_updated_on' => '2023-04-06 15:44:41',
    //             'notifications' => [$notificationID],
    //             'users' => [],
    //         ],
    //         $this->header,
    //     );

    //     $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
    //     ->assertJsonStructure([
    //         'message',
    //         'data',
    //     ]);

    //     $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
    //     $teamId = $contentCreateTeam['data'];

    //     // create user
    //     $responseCreateUser = $this->json(
    //         'POST',
    //         self::TEST_URL_USER,
    //         [
    //             'firstname' => 'Firstname',
    //             'lastname' => 'Lastname',
    //             'email' => 'firstname.lastname.123456789@test.com',
    //             'password' => 'Passw@rd1!',
    //             'sector_id' => 1,
    //             'organisation' => 'Test Organisation',
    //             'bio' => 'Test Biography',
    //             'domain' => 'https://testdomain.com',
    //             'link' => 'https://testlink.com/link',
    //             'orcid' => " https://orcid.org/75697342",
    //             'contact_feedback' => 1,
    //             'contact_news' => 1,
    //             'mongo_id' => 1234566,
    //             'mongo_object_id' => "12345abcde",
    //         ],
    //         $this->header,
    //     );
    //     $responseCreateUser->assertStatus(201);
    //     $contentCreateUser = $responseCreateUser->decodeResponseJson();
    //     $userId = $contentCreateUser['data'];

    //     $responseCreateDataset = $this->json(
    //         'POST',
    //         self::TEST_URL_DATASET,
    //         [
    //             'team_id' => $teamId,
    //             'user_id' => $userId,
    //             'metadata' => $this->metadata,
    //             'create_origin' => Dataset::ORIGIN_MANUAL,
    //             'status' => Dataset::STATUS_ACTIVE,
    //         ],
    //         $this->header
    //     );

    //     $responseCreateDataset->assertStatus(201);
    //     $datasetId = $responseCreateDataset['data'];

    //     $dataset1 = Dataset::with('versions')->where('id', $datasetId)->first();

    //     $this->assertTrue((count($dataset1->versions)) === 1);
    //     $updatedMetadata = $this->metadata;

    //     $updatedMetadata['metadata']['summary']['title'] = 'Updated Metadata Title 123';

    //     $responseUpdateDataset = $this->json(
    //         'PUT',
    //         self::TEST_URL_DATASET . '/' . $datasetId,
    //         [
    //             'team_id' => $teamId,
    //             'user_id' => $userId,
    //             'metadata' => $updatedMetadata,
    //             'create_origin' => Dataset::ORIGIN_MANUAL,
    //             'status' => Dataset::STATUS_ACTIVE,
    //         ],
    //         $this->header
    //     );

    //     $responseUpdateDataset->assertStatus(200);

    //     $version = DatasetVersion::where('dataset_id', $datasetId)->get();

    //     $this->assertTrue((count($version)) === 2);

    //     $this->assertEquals($version[0]->version, 1);
    //     $this->assertEquals($version[1]->version, 2);

    //     $this->assertEquals(
    //         $version[0]->metadata['metadata']['summary']['title'],
    //         $this->metadata['metadata']['summary']['title']
    //     );
    //     $this->assertEquals(
    //         $version[1]->metadata['metadata']['summary']['title'],
    //         $updatedMetadata['metadata']['summary']['title']
    //     );

    //     // assert that changing the status does not create a new version
    //     $responseChangeStatusDataset = $this->json(
    //         'PATCH',
    //         self::TEST_URL_DATASET . '/' . $datasetId,
    //         [
    //             'status' => Dataset::STATUS_DRAFT,
    //         ],
    //         $this->header
    //     );

    //     $responseChangeStatusDataset->assertStatus(200);

    //     $version = DatasetVersion::where('dataset_id', $datasetId)->get();

    //     $this->assertTrue((count($version)) === 2);

    //     $this->assertEquals($version[0]->version, 1);
    //     $this->assertEquals($version[1]->version, 2);

    //     $this->assertEquals(
    //         $version[0]->metadata['metadata']['summary']['title'],
    //         $this->metadata['metadata']['summary']['title']
    //     );
    //     $this->assertEquals(
    //         $version[1]->metadata['metadata']['summary']['title'],
    //         'Updated Metadata Title 123'
    //     );

    //     $responseDeleteDataset = $this->json(
    //         'DELETE',
    //         self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
    //         [],
    //         $this->header
    //     );

    //     $responseDeleteDataset->assertStatus(200);

    //     // Confirm DatasetVersions associated with this Dataset have also been (soft) deleted
    //     $versions = DatasetVersion::withTrashed()->where('dataset_id', $datasetId);
    //     foreach ($versions as $v) {
    //         $this->assertTrue($v->deleted_at !== null);
    //     }

    //     $responseDeleteTeam = $this->json(
    //         'DELETE',
    //         self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
    //         [],
    //         $this->header
    //     );

    //     $responseDeleteTeam->assertStatus(200);

    //     $responseDeleteUser = $this->json(
    //         'DELETE',
    //         self::TEST_URL_USER . '/' . $userId,
    //         [],
    //         $this->header
    //     );

    //     $responseDeleteUser->assertStatus(200);
    // }


    public function test_create_dataset_different_gwdm_versions(): void
    {
        $original_gwdm_version = Config::get("metadata.GWDM.version");
        //set the GWDM to version 1.0
        Config::set('metadata.GWDM.version', '1.0');
        $currentMetadata = $this->getMetadata();


        $originalPublisherName = $currentMetadata['metadata']['summary']['publisher']['publisherName'];
        $originalPhysicalSampleAvailability = $currentMetadata['metadata']['coverage']['physicalSampleAvailability'];

        $team = Team::first();
        $user = User::first();
        //create a dataset
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'metadata' => $currentMetadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );

        $responseCreateDataset->assertStatus(201);

        //check the metadata has recorded the right gwdm version
        $datasetId = $responseCreateDataset['data'];
        $dataset1 = DatasetVersion::where('dataset_id', $datasetId)->first();
        $dataset1GwdmVersion = $dataset1['metadata']['gwdmVersion'];
        $this->assertEquals($dataset1GwdmVersion, "1.0");

        $recordedPublisherName = $dataset1['metadata']['metadata']['summary']['publisher']['publisherName'];
        $recordedPhysicalSampleAvailability = $dataset1['metadata']['metadata']['coverage']['physicalSampleAvailability'];


        $this->assertNotEquals($recordedPublisherName, $originalPublisherName);
        $this->assertEquals($recordedPublisherName, $team->name);

        $this->assertEquals($recordedPhysicalSampleAvailability, $originalPhysicalSampleAvailability);


        //change to GWDM 1.1
        Config::set('metadata.GWDM.version', '1.1');
        $currentMetadata = $this->getMetadata();
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'metadata' => $currentMetadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );

        $responseUpdateDataset->assertStatus(200);

        $datasetId = $responseCreateDataset['data'];
        //get the 2nd version of the metadata that was just updated
        $dataset2 = DatasetVersion::where('dataset_id', $datasetId)->first();
        //check this has used the newer GWDM 1.1
        $dataset2GwdmVersion = $dataset2['metadata']['gwdmVersion'];
        $this->assertEquals($dataset2GwdmVersion, "1.1");

        $recordedBioligcalSamples = $dataset2['metadata']['metadata']['coverage']['biologicalsamples'];
        $this->assertEquals($recordedBioligcalSamples, $originalPhysicalSampleAvailability);

        Config::set('metadata.GWDM.version', $original_gwdm_version);

        //change to GWDM 2.0
        Config::set('metadata.GWDM.version', '2.0');
        $currentMetadata = $this->getMetadata();
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'metadata' => $currentMetadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );

        $responseUpdateDataset->assertStatus(200);

        $datasetId = $responseCreateDataset['data'];
        //get the 2nd version of the metadata that was just updated
        $dataset2 = DatasetVersion::where('dataset_id', $datasetId)->first();
        //check this has used the newer GWDM 2.0
        $dataset2GwdmVersion = $dataset2['metadata']['gwdmVersion'];
        $this->assertEquals($dataset2GwdmVersion, "2.0");

        $recordedTissuesSamples = $dataset2['metadata']['metadata']['tissuesSampleCollection'];

        $this->assertCount(2, $recordedTissuesSamples);
        Config::set('metadata.GWDM.version', $original_gwdm_version);


    }


    public function test_get_dataset_with_multiple_versions(): void
    {
        $initialMetadata = $this->getMetadata();
        $team = Team::first();
        $user = User::first();

        $nInitialDatasets = Dataset::where("team_id", $team->id)->count();

        //create a dataset
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'metadata' => $initialMetadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );
        $responseCreateDataset->assertStatus(201);
        $datasetId = $responseCreateDataset['data'];

        $updatedMetadata = $this->getMetadata();
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'team_id' => $team->id,
                'user_id' => $user->id,
                'metadata' => $updatedMetadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );
        $responseUpdateDataset->assertStatus(200);


        $responseGetDataset = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/' . $datasetId,
            $this->header
        );

        $this->assertCount(1, $responseGetDataset['data']['versions']);


    }

    public function test_get_datasets_with_multiple_versions(): void
    {
        $this->seed([
            MinimalUserSeeder::class,
            DatasetSeeder::class, //10 datasets
            DatasetSeeder::class, //another 10
            DatasetVersionSeeder::class,//seed the 20 with random number of versions
        ]);

        $responseGetDatasets = $this->json(
            'GET',
            self::TEST_URL_DATASET,
            $this->header
        );

        $responseGetDatasets->assertStatus(200);
        $this->assertCount(20, $responseGetDatasets['data']);

        foreach ($responseGetDatasets['data'] as $dataset) {
            $this->assertArrayHasKey('latest_metadata', $dataset);
            $this->assertNotEmpty($dataset['latest_metadata']);

            $nVersions = DatasetVersion::where("dataset_id", $dataset['id'])->count();
            //the version number should be equal to the total number of versions
            $this->assertEquals($nVersions, $dataset['latest_metadata']['version']);

        }
    }

    public function test_get_datasets_with_changing_per_page(): void
    {
        $this->seed([
            MinimalUserSeeder::class,
            DatasetSeeder::class, //10 datasets
            DatasetSeeder::class, //another 10
            DatasetVersionSeeder::class,//seed the 20 with random number of versions
        ]);

        $responseGetDatasets = $this->json(
            'GET',
            self::TEST_URL_DATASET,
            $this->header
        );

        $responseGetDatasets->assertStatus(200);
        $this->assertCount(20, $responseGetDatasets['data']);

        foreach ($responseGetDatasets['data'] as $dataset) {
            $this->assertArrayHasKey('latest_metadata', $dataset);
            $this->assertNotEmpty($dataset['latest_metadata']);
        }
    }



}
