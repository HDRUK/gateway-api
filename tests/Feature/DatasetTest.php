<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\NamedEntities;
use Illuminate\Support\Carbon;
use Tests\Traits\Authorization;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\LinkageExtraction;

class DatasetTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET = '/api/v1/datasets';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';

    protected $metadata;
    protected $metadataAlt;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
        $this->metadataAlt = $this->metadata;
        $this->metadataAlt['metadata']['summary']['title'] = 'ABC title';
    }

    /**
     * Get All Datasets with success
     *
     * @return void
     */
    public function test_get_all_datasets_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL_DATASET, [], $this->header);
        $response->assertJsonStructure([
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
        $response->assertStatus(200);
    }

    /**
     * Get All Datasets for a given team with success
     *
     * @return void
     */
    public function test_get_all_team_datasets_with_success(): void
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

        // Create the new team
        $teamName = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');

        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => $teamName,
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
        $teamId1 = $contentCreateTeam['data'];

        //create a 2nd team
        $teamName = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => $teamName,
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
        $teamId2 = $contentCreateTeam['data'];

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

        $specificTime = Carbon::parse('2023-01-01 00:00:00');
        Carbon::setTestNow($specificTime);

        // create dataset
        $labelDataset1 = 'XYZ DATASET';
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId1,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $responseCreateDataset->assertStatus(201);

        $datasetId1 = $responseCreateDataset['data'];

        //create a 2nd one
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);
        $labelDataset2 = 'ABC DATASET';
        $responseCreateDataset2 = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId1,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $responseCreateDataset2->assertStatus(201);
        $datasetId2 = $responseCreateDataset2['data'];

        //create a 3rd one which is owned by the 2nd team
        $specificTime = Carbon::parse('2023-03-01 00:00:00');
        Carbon::setTestNow($specificTime);
        $labelDataset3 = 'Other Team DATASET';
        $responseCreateDataset3 = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId2,
                'user_id' => $userId,
                'metadata' => $this->metadataAlt,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $responseCreateDataset3->assertStatus(201);
        $datasetId3 = $responseCreateDataset3['data'];

        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET,
            [],
            $this->header
        );
        $response->assertStatus(200);

        $this->assertCount(3, $response['data']);
        $response->assertJsonStructure([
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);

        /*
        * Test filtering by dataset title being ABC (datasetAlt)
        */


        /* NOTE -  Calum 5/1/2024
                Test is currently turned off because the model is calling raw SQL function JSON_UNQUOTE
                which is not known to SQLite and therefore the test will fail
           NOTE -  Calum 10/1/2024
                Loki has been investigating this and coming up with a solution
                There may be some other things not quite right due to sqlite/MySQL differences
                This is know about and is being resolved...
        */
        /*
        $response = $this->json('GET', self::TEST_URL_DATASET .
            '?title=ABC',
            [], $this->header
        );
        $response->assertStatus(200);
        $this->assertCount(1,$response['data']);
        */

        /*
        * Sort so that the newest dataset is first in the list
        */
        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&sort=created:desc',
            [],
            $this->header
        );
        $first = Carbon::parse($response['data'][0]['created']);
        $second = Carbon::parse($response['data'][1]['created']);

        $this->assertTrue($first->gt($second));



        //create an archived dataset from team1
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);
        $labelDataset2 = 'Archived ABC DATASET';
        $responseCreateDatasetArchived = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId1,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ARCHIVED,
            ],
            $this->header,
        );
        $responseCreateDatasetArchived->assertStatus(201);

        /*
        * use the endpoint /api/v1/datasets/count to find unique values of the field 'status'
        */
        $responseCount = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '/count/status?team_id=' . $teamId1,
            [],
            $this->header
        );
        $responseCount->assertStatus(200);
        $countActive = $responseCount['data']['ACTIVE'];
        $countDraft = $responseCount['data']['DRAFT'];
        $countArchived = $responseCount['data']['ARCHIVED'];

        $this->assertTrue($countActive === 1);
        $this->assertTrue($countDraft === 1);
        $this->assertTrue($countArchived === 1);

        $responseActiveDatasets = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&status=ACTIVE',
            [],
            $this->header
        );
        $responseActiveDatasets->assertStatus(200);

        $this->assertCount(1, $responseActiveDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseActiveDatasets['data'][0]);
        $this->assertNotEmpty($responseActiveDatasets['data'][0]['latest_metadata']);

        $responseDraftDatasets = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&status=DRAFT',
            [],
            $this->header
        );
        $responseDraftDatasets->assertStatus(200);

        $this->assertCount(1, $responseDraftDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseDraftDatasets['data'][0]);
        $this->assertNotEmpty($responseDraftDatasets['data'][0]['latest_metadata']);

        $responseArchivedDatasets = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&status=ARCHIVED',
            [],
            $this->header
        );
        $responseArchivedDatasets->assertStatus(200);

        $this->assertCount(1, $responseArchivedDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseArchivedDatasets['data'][0]);
        $this->assertNotEmpty($responseArchivedDatasets['data'][0]['latest_metadata']);


        /*
        * reverse this sorting
        */
        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&sort=created:asc',
            [],
            $this->header
        );
        $first = Carbon::parse($response['data'][0]['created']);
        $second = Carbon::parse($response['data'][1]['created']);

        $this->assertTrue($first->lt($second));

        /*
        * Sort A-Z on the dataset label
        */
        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&sort=label:asc',
            [],
            $this->header
        );

        /*
        * Sort Z-A on the dataset label
        */
        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&sort=label:desc',
            [],
            $this->header
        );

        /*
        * Sort Z-A on the metadata title
        */
        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&sort=properties/summary/title:desc',
            [],
            $this->header
        );

        /*
        * fail if a bad direction has been given for sorting
        */
        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET .
            '?team_id=' . $teamId1 .
            '&sort=created:blah',
            [],
            $this->header
        );
        $response->assertStatus(400);

        // delete datasets
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId1 . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId2 . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId3 . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
    }

    /**
     * Get Dataset by Id with success
     *
     * @return void
     */
    public function test_get_one_dataset_by_id_with_success(): void
    {
        // create team
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

        // Create the new team
        $teamName = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => $teamName,
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

        // create active dataset
        $responseCreateActiveDataset = $this->json(
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

        $responseCreateActiveDataset->assertStatus(201);
        $contentCreateActiveDataset = $responseCreateActiveDataset->decodeResponseJson();
        $activeDatasetId = $contentCreateActiveDataset['data'];

        // get one active dataset
        $responseGetOneActive = $this->json('GET', self::TEST_URL_DATASET . '/' . $activeDatasetId, [], $this->header);

        $responseGetOneActive->assertJsonStructure([
            'message',
            'data' => [
                'named_entities',
                'collections',
                'publications',
                'versions',
                'durs_count',
                'publications_count',
            ]
        ]);
        $responseGetOneActive->assertStatus(200);

        $respArrayActive = $responseGetOneActive->decodeResponseJson();
        $this->assertArrayHasKey('named_entities', $respArrayActive['data']);

        // Assert named entities contain name

        foreach ($respArrayActive['data']['named_entities'] as $entity) {
            // Assert that the array contains a key named 'name'
            $this->assertArrayHasKey('name', $entity);
            $this->assertNotNull($entity['name'], 'The "name" key should not have a null value.');

            // Assert that the array contains a key named 'id'
            $this->assertArrayHasKey('id', $entity);
            $this->assertNotNull($entity['id'], 'The "id" key should not have a null value.');

            // Retrieve the named entity from the database
            $namedEntity = NamedEntities::find($entity['id']);
            $this->assertNotNull($namedEntity, 'The named entity should exist in the database.');

            // Compare each field in the response array with the corresponding field in the database
            $this->assertEquals($namedEntity->name, $entity['name'], 'The name in the response does not match the name in the database.');
        }



        /*
        // - need to temporary disable - this wont be filled because TED runs as a job
        if(config('ted.enabled')) {
            $this->assertNotEmpty($respArrayActive['data']['named_entities']);
        };
        */
        $this->assertArrayHasKey(
            'linked_dataset_versions',
            $respArrayActive['data']['versions'][0]
        );

        // delete active dataset
        $responseDeleteActiveDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $activeDatasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteActiveDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteActiveDataset->assertStatus(200);

        // create draft dataset
        $responseCreateDraftDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );

        $responseCreateDraftDataset->assertStatus(201);
        $contentCreateDraftDataset = $responseCreateDraftDataset->decodeResponseJson();
        $draftDatasetId = $contentCreateDraftDataset['data'];

        // get one draft dataset
        $responseGetOneDraft = $this->json('GET', self::TEST_URL_DATASET . '/' . $draftDatasetId, [], $this->header);

        $responseGetOneDraft->assertJsonStructure([
            'message',
            'data' => [
                'named_entities',
                'collections',
                'publications',
                'versions',
                'durs_count',
                'publications_count',
            ]
        ]);
        $responseGetOneDraft->assertStatus(200);

        $respArrayDraft = $responseGetOneDraft->decodeResponseJson();
        $this->assertArrayHasKey('named_entities', $respArrayDraft['data']);

        // The named_entities field is empty for draft datasets.
        // The TermExtraction job is responsible for populating the named_entities field,
        // is not run for draft datasets, thus the field remains empty and the following breaks the code.

        $this->assertEmpty($respArrayDraft['data']['named_entities']);

        // delete draft dataset
        $responseDeleteDraftDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $draftDatasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDraftDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDraftDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    /**
     * Create/archive/unarchive Dataset with success
     *
     * @return void
     */
    public function test_create_archive_delete_dataset_with_success(): void
    {

        // create team
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

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'orcid' => "https://orcid.org/75697342",
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

        // create dataset
        $labelDataset = 'label dataset ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        Queue::assertNotPushed(LinkageExtraction::class);
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // archive dataset
        $responseArchiveDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [],
            $this->header
        );
        $responseArchiveDataset->assertJsonStructure([
            'message'
        ]);
        $responseArchiveDataset->assertStatus(200);

        // unarchive dataset
        $responseUnarchiveDataset = $this->json(
            'PATCH',
            self::TEST_URL_DATASET . '/' . $datasetId . '?unarchive',
            [
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header
        );
        $responseUnarchiveDataset->assertJsonStructure([
            'message'
        ]);
        Queue::assertPushed(LinkageExtraction::class);
        $responseUnarchiveDataset->assertStatus(200);

        // change dataset status
        $responseChangeStatusDataset = $this->json(
            'PATCH',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header
        );
        $responseChangeStatusDataset->assertJsonStructure([
            'message'
        ]);
        $responseChangeStatusDataset->assertStatus(200);

        // permanent delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    /**
     * Create/update/delete Dataset with success
     *
     * @return void
     */
    public function test_create_update_delete_dataset_with_success(): void
    {
        // create team
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

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'orcid' => "https://orcid.org/75697342",
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

        // create dataset
        $labelDataset = 'label dataset ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
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
        Queue::assertPushed(LinkageExtraction::class);
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // update dataset
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );

        $contentUpdateDataset = $responseUpdateDataset->decodeResponseJson();
        $responseUpdateDataset->assertStatus(200);

        // permanent delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    public function test_create_dataset_fails_with_invalid_origin(): void
    {
        // create team
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

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'orcid' => "https://orcid.org/75697342",
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

        // create dataset
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => 'OPTION_DOESNT_EXIST',
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $responseCreateDataset->assertStatus(400);
        $createDataset = $responseCreateDataset->decodeResponseJson();
        $this->assertEquals($createDataset['status'], 'INVALID_ARGUMENT');
        $this->assertEquals($createDataset['errors'][0]['reason'], 'IN');
        $this->assertEquals($createDataset['errors'][0]['field'], 'create_origin');

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    /**
     * Download Dataset table export with success
     *
     * @return void
     */
    public function test_download_dataset_table_with_success(): void
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // create team
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

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'orcid' => "https://orcid.org/75697342",
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

        // create dataset
        $labelDataset = 'label dataset ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
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
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        $responseDownload = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/export',
            [],
            $this->header,
        );

        $content = $responseDownload->streamedContent();
        $responseDownload->assertHeader('Content-Disposition', 'attachment;filename="Datasets.csv"');
        $this->assertEquals(
            substr($content, 0, 5),
            "Title"
        );

        // test dataset_id query parameter
        $responseDownload = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/export?dataset_id=' . $datasetId,
            [],
            $this->header,
        );
        $responseDownload->assertStatus(200);
        $content = $responseDownload->streamedContent();
        $responseDownload->assertHeader('Content-Disposition', 'attachment;filename="Datasets.csv"');
        $this->assertEquals(
            substr($content, 0, 5),
            "Title"
        );

        // permanent delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);

    }

    public function test_can_download_mock_dataset_structural_metadata_file()
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // Mock the storage disk
        Storage::fake('mock');

        // Put a fake file in the mock disk
        $filePath = 'structural_metadata_template.xlsx';
        Storage::disk('mock')->put($filePath, 'fake content');

        // Mock the config
        Config::set('mock_data.template_dataset_structural_metadata', $filePath);
        Config::set('statuscodes.STATUS_OK.code', 200);

        // Make the request
        $response = $this->get('/api/v1/datasets/export/mock?type=template_dataset_structural_metadata');

        // Assert the file is downloaded
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=' . $filePath);

        // Clean up
        Storage::disk('mock')->delete($filePath);
    }

    public function test_can_download_mock_dataset_metadata_file()
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // Mock the storage disk
        Storage::fake('mock');

        // Put a fake file in the mock disk
        $filePath = 'example_dataset_metadata.xlsx';
        Storage::disk('mock')->put($filePath, 'fake content');

        // Mock the config
        Config::set('mock_data.mock_dataset_metadata', $filePath);
        Config::set('statuscodes.STATUS_OK.code', 200);

        // Make the request
        $response = $this->get('/api/v1/datasets/export/mock?type=dataset_metadata');

        // Assert the file is downloaded
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=' . $filePath);

        // Clean up
        Storage::disk('mock')->delete($filePath);
    }

    public function test_download_mock_file_with_file_not_found()
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // Mock the config
        Config::set('mock_data.dataset_structural_metadata', 'non_existent_file.json');

        // Make the request
        $response = $this->get('/api/v1/datasets/export/mock?type=dataset_structural_metadata');

        // Assert the file is not found
        $response->assertStatus(404);
        $response->assertJson(['error' => 'File not found.']);
    }

    public function test_update_dataset_doesnt_create_new_version(): void
    {
        // create team
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

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
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
                'orcid' => "https://orcid.org/75697342",
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

        // create dataset
        $labelDataset = 'label dataset test title';
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
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        $this->metadataAlt['metadata']['summary']['title'] = 'updated test title';

        // update dataset
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadataAlt,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $contentUpdateDataset = $responseUpdateDataset->decodeResponseJson();
        $responseUpdateDataset->assertStatus(200);

        $dsv = DatasetVersion::where('dataset_id', $datasetId)->get();
        $this->assertTrue(count($dsv) === 1);

        // only looping here because this is a collection, and calling 'first'
        // would defeat the point of this test
        foreach ($dsv as $d) {
            $this->assertTrue($d->metadata['metadata']['summary']['title'] === 'updated test title');
        }
    }
}
