<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\NamedEntities;
use Illuminate\Support\Carbon;
use Tests\Traits\Authorization;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\MockExternalApis;
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

    public const TEST_URL_DATASET_V2 = '/api/v2/datasets';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';

    protected $metadata;
    protected $metadataAlt;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
        $this->metadataAlt = $this->metadata;
        $this->metadataAlt['metadata']['summary']['title'] = 'ABC title';

        // Generate non-admin user for general usage
        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdminJwt,
        ];

        // generate jwt for a different user
        // This user can be used to test as a member of another team
        $this->authorisationUser(false, 2);
        $this->nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        $this->nonAdmin2User = $this->getUserFromJwt($this->nonAdmin2Jwt);
        $this->headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdmin2Jwt,
        ];
    }

    /**
     * Get All Datasets with success
     *
     * @return void
     */
    public function test_get_all_datasets_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL_DATASET_V2, [], $this->headerNonAdmin);
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
        $notificationID = $this->create_notification();

        // Create the new team
        $teamName = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');

        $teamId1 = $this->create_team([$this->nonAdminUser['id']], [$notificationID]);

        //create a 2nd team
        $teamId2 = $this->create_team([$this->nonAdmin2User['id']], [$notificationID]);

        $specificTime = Carbon::parse('2023-01-01 00:00:00');
        Carbon::setTestNow($specificTime);

        // create dataset
        $labelDataset1 = 'XYZ DATASET';
        $responseCreateDataset = $this->json(
            'POST',
            $this->team_datasets_url($teamId1),
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->headerNonAdmin,
        );

        $responseCreateDataset->assertStatus(201);

        $datasetId1 = $responseCreateDataset['data'];

        //create a 2nd active one
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);
        $labelDataset2 = 'ABC DATASET';
        $responseCreateDataset2 = $this->json(
            'POST',
            $this->team_datasets_url($teamId1),
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->headerNonAdmin,
        );
        $responseCreateDataset2->assertStatus(201);
        $datasetId2 = $responseCreateDataset2['data'];

        //create a 3nd one which is draft
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);
        $labelDataset3 = 'ABC DATASET';
        $responseCreateDataset3 = $this->json(
            'POST',
            $this->team_datasets_url($teamId1),
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->headerNonAdmin,
        );
        $responseCreateDataset3->assertStatus(201);
        $datasetId3 = $responseCreateDataset3['data'];

        //create a 4th one which is owned by the 2nd team
        $specificTime = Carbon::parse('2023-03-01 00:00:00');
        Carbon::setTestNow($specificTime);
        $labelDataset4 = 'Other Team DATASET';
        $responseCreateDataset4 = $this->json(
            'POST',
            $this->team_datasets_url($teamId2),
            [
                'metadata' => $this->metadataAlt,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->headerNonAdmin2,
        );
        $responseCreateDataset4->assertStatus(201);
        $datasetId4 = $responseCreateDataset4['data'];

        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET_V2,
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(200);

        $this->assertCount(4, $response['data']);
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
        $response = $this->json('GET', self::TEST_URL_DATASET_V2 .
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
            $this->team_datasets_url($teamId1) .
            '?sort=created:desc',
            [],
            $this->headerNonAdmin
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
            $this->team_datasets_url($teamId1),
            [
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ARCHIVED,
            ],
            $this->headerNonAdmin,
        );
        $responseCreateDatasetArchived->assertStatus(201);

        /*
        * use the endpoint /api/v2/teams/{teamId}/datasets/count to find unique values of the field 'status'
        */
        $responseCount = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/count/status',
            [],
            $this->headerNonAdmin
        );
        $responseCount->assertStatus(200);
        $countActive = $responseCount['data']['ACTIVE'];
        $countDraft = $responseCount['data']['DRAFT'];
        $countArchived = $responseCount['data']['ARCHIVED'];

        $this->assertEquals(2, $countActive);
        $this->assertEquals(1, $countDraft);
        $this->assertEquals(1, $countArchived);

        $responseActiveDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1),
            [],
            $this->headerNonAdmin
        );
        $responseActiveDatasets->assertStatus(200);

        $this->assertCount(2, $responseActiveDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseActiveDatasets['data'][0]);
        $this->assertNotEmpty($responseActiveDatasets['data'][0]['latest_metadata']);

        $responseDraftDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/status/draft',
            [],
            $this->headerNonAdmin
        );
        $responseDraftDatasets->assertStatus(200);

        $this->assertCount(1, $responseDraftDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseDraftDatasets['data'][0]);
        $this->assertNotEmpty($responseDraftDatasets['data'][0]['latest_metadata']);

        $responseArchivedDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/status/archived',
            [],
            $this->headerNonAdmin
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
            $this->team_datasets_url($teamId1) .
            '?sort=created:asc',
            [],
            $this->headerNonAdmin
        );
        $first = Carbon::parse($response['data'][0]['created']);
        $second = Carbon::parse($response['data'][1]['created']);

        $this->assertTrue($first->lt($second));

        /*
        * fail if a bad direction has been given for sorting
        */
        $response = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) .
            '?sort=created:blah',
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(500);


        for ($i = 1; $i <= 4; $i++) {
            // delete dataset
            $responseDeleteDataset = $this->json(
                'DELETE',
                $this->team_datasets_url($teamId1) . //TODO: this shouldn't be able to delete the dataset belonging to the other team
                '/' . $i,
                [],
                $this->headerNonAdmin
            );
            $responseDeleteDataset->assertJsonStructure([
                'message'
            ]);
        }

        for ($i = 1; $i <= 2; $i++) {
            // delete team
            $responseDeleteTeam = $this->json(
                'DELETE',
                self::TEST_URL_TEAM . '/' . ${'teamId' . $i} . '?deletePermanently=true',
                [],
                $this->header
            );

            $responseDeleteTeam->assertJsonStructure([
                'message'
            ]);
            $responseDeleteTeam->assertStatus(200);
        }

    }





    /**
     * Get Dataset by Id with success
     *
     * @return void
     */
    public function test_get_one_dataset_by_id(): void
    {
        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->create_notification();

        // Create the new team
        $teamId = $this->create_team([], [$notificationID]);

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

        // Create a second team for testing
        $teamId2 = $this->create_team([], [$notificationID]);

        // create active dataset
        $responseCreateActiveDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET_V2,
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

        // get one active dataset via V2 endpoint
        $responseGetOneActive = $this->json('GET', self::TEST_URL_DATASET_V2 . '/' . $activeDatasetId, [], $this->header);

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

        // get one active dataset via V2 teams endpoint
        $responseGetAll = $this->json('GET', $this->team_datasets_url($teamId), [], $this->header);

        $responseGetOneActive = $this->json('GET', $this->team_datasets_url($teamId) . '/' . $activeDatasetId, [], $this->header);

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

        // try and fail get one active dataset via V2 teams endpoint with wrong team id
        $responseGetOneActive = $this->json('GET', $this->team_datasets_url($teamId2) . '/' . $activeDatasetId, [], $this->header);
        $responseGetOneActive->assertStatus(404);

        // delete active dataset
        $responseDeleteActiveDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET_V2 . '/' . $activeDatasetId,
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
            self::TEST_URL_DATASET_V2,
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

        // fail to get draft dataset via V2 endpoint because only active are returned
        $responseGetOneDraftV2 = $this->json('GET', self::TEST_URL_DATASET_V2 . '/' . $draftDatasetId, [], $this->header);

        $responseGetOneDraftV2->assertStatus(404);
        $responseGetOneDraftV2->assertJson(['message' => 'Dataset not found']);


        // delete draft dataset
        $responseDeleteDraftDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET_V2 . '/' . $draftDatasetId,
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
    public function test_create_archive_update_delete_dataset_with_success(): void
    {

        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->create_notification();

        // Create the new team
        $teamId = $this->create_team([], [$notificationID]);

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
            self::TEST_URL_DATASET_V2,
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
            'PATCH',
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
            [
                'status' => Dataset::STATUS_ARCHIVED,
            ],
            $this->header
        );
        $responseArchiveDataset->assertJsonStructure([
            'message'
        ]);
        $responseArchiveDataset->assertStatus(200);

        // unarchive dataset
        $responseUnarchiveDataset = $this->json(
            'PATCH',
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
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
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
            [
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header
        );
        $responseChangeStatusDataset->assertJsonStructure([
            'message'
        ]);
        $responseChangeStatusDataset->assertStatus(200);

        // update dataset
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
            [
               'team_id' => $teamId,
               'user_id' => $userId,
               'metadata' => $this->metadata,
               'create_origin' => Dataset::ORIGIN_MANUAL,
               'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $contentUpdateDataset = $responseUpdateDataset->decodeResponseJson();
        $responseUpdateDataset->assertStatus(200);

        $responseGetDataset = $this->json(
            'GET',
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
            [],
            $this->header,
        );
        $contentGetDataset = $responseGetDataset->decodeResponseJson();
        $responseGetDataset->assertStatus(200);
        $this->assertEquals(Dataset::STATUS_ACTIVE, $contentGetDataset['data']['status']);

        // delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
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
     * Create/archive/unarchive Team Dataset with success
     *
     * @return void
     */
    public function test_create_archive_update_delete_team_dataset_with_success(): void
    {

        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->create_notification();

        // Create the new team
        $teamId = $this->create_team([], [$notificationID]);

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
            $this->team_datasets_url($teamId),
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
            'PATCH',
            $this->team_datasets_url($teamId) . '/' . $datasetId,
            [
                'status' => Dataset::STATUS_ARCHIVED,
            ],
            $this->header
        );
        $responseArchiveDataset->assertJsonStructure([
            'message'
        ]);
        $responseArchiveDataset->assertStatus(200);

        // unarchive dataset
        $responseUnarchiveDataset = $this->json(
            'PATCH',
            $this->team_datasets_url($teamId) . '/' . $datasetId,
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
            $this->team_datasets_url($teamId) . '/' . $datasetId,
            [
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header
        );
        $responseChangeStatusDataset->assertJsonStructure([
            'message'
        ]);
        $responseChangeStatusDataset->assertStatus(200);

        // update dataset
        $responseUpdateDataset = $this->json(
            'PUT',
            $this->team_datasets_url($teamId) . '/' . $datasetId,
            [
               'team_id' => $teamId,
               'user_id' => $userId,
               'metadata' => $this->metadata,
               'create_origin' => Dataset::ORIGIN_MANUAL,
               'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        $contentUpdateDataset = $responseUpdateDataset->decodeResponseJson();
        $responseUpdateDataset->assertStatus(200);

        $responseGetDataset = $this->json(
            'GET',
            $this->team_datasets_url($teamId) . '/' . $datasetId,
            [],
            $this->header,
        );
        $contentGetDataset = $responseGetDataset->decodeResponseJson();
        $responseGetDataset->assertStatus(200);
        $this->assertEquals(Dataset::STATUS_ACTIVE, $contentGetDataset['data']['status']);

        // delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            $this->team_datasets_url($teamId) . '/' . $datasetId,
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

    public function test_update_dataset_doesnt_create_new_version(): void
    {
        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->create_notification();

        // Create the new team
        $teamId = $this->create_team([], [$notificationID]);

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
            self::TEST_URL_DATASET_V2,
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
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
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

    public function test_update_team_dataset_doesnt_create_new_version(): void
    {
        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->create_notification();

        // Create the new team
        $teamId = $this->create_team([], [$notificationID]);

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
            $this->team_datasets_url($teamId),
            [
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
            $this->team_datasets_url($teamId) . '/' . $datasetId,
            [
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

    private function team_datasets_url(int $teamId)
    {
        return 'api/v2/teams/' . $teamId . '/datasets';
    }

    private function create_notification()
    {
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
        return $contentNotification['data'];
    }

    private function create_team(array $userIds, array $notificationIds)
    {
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
                'notifications' => $notificationIds,
                'users' => $userIds,
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        return $contentCreateTeam['data'];
    }
}
