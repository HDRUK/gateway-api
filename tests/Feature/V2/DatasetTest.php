<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\NamedEntities;
use App\Models\DatasetVersion;
use Illuminate\Support\Carbon;
use App\Jobs\LinkageExtraction;
use Tests\Traits\Authorization;
use App\Http\Enums\TeamMemberOf;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Queue;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class DatasetTest extends TestCase
{
    use FastRefreshDatabase;
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
    protected $nonAdminJwt;
    protected $nonAdminUser;
    protected $headerNonAdmin;
    protected $nonAdmin2User;
    protected $nonAdmin2Jwt;
    protected $headerNonAdmin2;

    public function setUp(): void
    {
        parent::setUp();

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
    public function test_v2_get_all_datasets_with_success(): void
    {
        // $this->setUp();
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
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * Get All Datasets for a given team with success
     *
     * @return void
     */
    public function test_get_all_team_datasets_with_success(): void
    {
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamName = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');

        $teamId1 = $this->createTeam([$this->nonAdminUser['id']], [$notificationID]);

        //create a 2nd team
        $teamId2 = $this->createTeam([$this->nonAdmin2User['id']], [$notificationID]);

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

        $responseCreateDataset->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

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
        $responseCreateDataset2->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
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
        $responseCreateDataset3->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
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
        $responseCreateDataset4->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $datasetId4 = $responseCreateDataset4['data'];

        $response = $this->json(
            'GET',
            self::TEST_URL_DATASET_V2,
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
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
        $responseCreateDatasetArchived->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        /*
        * use the endpoint /api/v2/teams/{teamId}/datasets/count to find unique values of the field 'status'
        */
        $responseCount = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/count/status',
            [],
            $this->headerNonAdmin
        );
        $responseCount->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $countActive = $responseCount['data']['ACTIVE'];
        $countDraft = $responseCount['data']['DRAFT'];
        $countArchived = $responseCount['data']['ARCHIVED'];

        $this->assertEquals(2, $countActive);
        $this->assertEquals(1, $countDraft);
        $this->assertEquals(1, $countArchived);

        // get active datsets in this team
        $responseActiveDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1),
            [],
            $this->headerNonAdmin
        );
        $responseActiveDatasets->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(2, $responseActiveDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseActiveDatasets['data'][0]);
        $this->assertNotEmpty($responseActiveDatasets['data'][0]['latest_metadata']);

        // get draft datsets in this team
        $responseDraftDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/status/draft',
            [],
            $this->headerNonAdmin
        );
        $responseDraftDatasets->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(1, $responseDraftDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseDraftDatasets['data'][0]);
        $this->assertNotEmpty($responseDraftDatasets['data'][0]['latest_metadata']);

        // get archived datsets in this team
        $responseArchivedDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/status/archived',
            [],
            $this->headerNonAdmin
        );
        $responseArchivedDatasets->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(1, $responseArchivedDatasets['data']);
        $this->assertArrayHasKey('latest_metadata', $responseArchivedDatasets['data'][0]);
        $this->assertNotEmpty($responseArchivedDatasets['data'][0]['latest_metadata']);

        /*
        * nonAdmin2 is not in this team, so count and active shoudl pass, but draft and archived should fail
        */
        $responseCount = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/count/status',
            [],
            $this->headerNonAdmin2
        );
        $responseCount->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // get active datsets in this team
        $responseActiveDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1),
            [],
            $this->headerNonAdmin2
        );
        $responseActiveDatasets->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // (fail to) get draft datsets in this team
        $responseDraftDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/status/draft',
            [],
            $this->headerNonAdmin2
        );
        $responseDraftDatasets->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        // (fail to) get archived datsets in this team
        $responseArchivedDatasets = $this->json(
            'GET',
            $this->team_datasets_url($teamId1) . '/status/archived',
            [],
            $this->headerNonAdmin2
        );
        $responseArchivedDatasets->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

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
        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));

        // test deletion with various permissions combinations
        for ($i = 1; $i <= 5; $i++) {
            if ($i !== 4) {
                // delete dataset
                $responseDeleteDataset = $this->json(
                    'DELETE',
                    $this->team_datasets_url($teamId1) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin
                );
                $responseDeleteDataset->assertJsonStructure([
                    'message'
                ]);
                // var_dump($responseDeleteDataset->decodeResponseJson());
                $responseDeleteDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
            } else {
                // attempt to
                // 1) delete with incorrect team (1), and incorrect user (1)
                // 2) delete with correct team (2) but incorrect user (1)
                // 3) delete with incorrect team (1) but correct user (2)
                //
                // then complete the deletion with team 2 and user 2
                $responseDeleteDataset = $this->json(
                    'DELETE',
                    $this->team_datasets_url($teamId1) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin
                );
                $responseDeleteDataset->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDataset->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

                $responseDeleteDataset = $this->json(
                    'DELETE',
                    $this->team_datasets_url($teamId2) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin
                );
                $responseDeleteDataset->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDataset->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

                $responseDeleteDataset = $this->json(
                    'DELETE',
                    $this->team_datasets_url($teamId1) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin2
                );
                $responseDeleteDataset->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDataset->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

                $responseDeleteDataset = $this->json(
                    'DELETE',
                    $this->team_datasets_url($teamId2) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin2
                );
                $responseDeleteDataset->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
            }
        }

        for ($i = 1; $i <= 2; $i++) {
            // delete team
            $this->deleteTeam($i);
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
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

        // Create a second team for testing
        $teamId2 = $this->createTeam([], [$notificationID]);

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

        $responseCreateActiveDataset->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreateActiveDataset = $responseCreateActiveDataset->decodeResponseJson();
        $activeDatasetId = $contentCreateActiveDataset['data'];

        // get one active dataset via V2 endpoint
        $responseGetOneActive = $this->json(
            'GET',
            self::TEST_URL_DATASET_V2 . '/' . $activeDatasetId,
            [],
            $this->header
        );

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
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // try and fail get one active dataset via V2 teams endpoint with wrong team id
        $responseGetOneActive = $this->json(
            'GET',
            $this->team_datasets_url($teamId2) . '/' . $activeDatasetId,
            [],
            $this->header
        );
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));

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
        $responseDeleteActiveDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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

        $responseCreateDraftDataset->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreateDraftDataset = $responseCreateDraftDataset->decodeResponseJson();
        $draftDatasetId = $contentCreateDraftDataset['data'];

        // fail to get draft dataset via V2 endpoint because only active are returned
        $responseGetOneDraftV2 = $this->json(
            'GET',
            self::TEST_URL_DATASET_V2 . '/' . $draftDatasetId,
            [],
            $this->header
        );

        $responseGetOneDraftV2->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
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
        $responseDeleteDraftDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // delete team
        $this->deleteTeam($teamId);

        // delete user
        $this->deleteUser($userId);
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
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

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
        $responseCreateDataset->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
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
        $responseArchiveDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $responseUnarchiveDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $responseChangeStatusDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $responseUpdateDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // check status has updated correctly
        $responseGetDataset = $this->json(
            'GET',
            self::TEST_URL_DATASET_V2 . '/' . $datasetId,
            [],
            $this->header,
        );
        $contentGetDataset = $responseGetDataset->decodeResponseJson();
        $responseGetDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
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
        $responseDeleteDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // delete team
        $this->deleteTeam($teamId);

        // delete user
        $this->deleteUser($userId);
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
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

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
        $responseCreateDataset->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
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
        $responseArchiveDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $responseUnarchiveDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $responseChangeStatusDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $responseUpdateDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $responseGetDataset = $this->json(
            'GET',
            $this->team_datasets_url($teamId) . '/' . $datasetId,
            [],
            $this->header,
        );
        $contentGetDataset = $responseGetDataset->decodeResponseJson();
        $responseGetDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
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
        $responseDeleteDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // delete team
        $this->deleteTeam($teamId);

        // delete user
        $this->deleteUser($userId);
    }

    public function test_update_dataset_doesnt_create_new_version(): void
    {
        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

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
        $responseCreateDataset->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
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
        $responseUpdateDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

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
        $responseCreateDataset->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
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
        $responseUpdateDataset->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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

    private function createNotification()
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

    private function createTeam(array $userIds, array $notificationIds)
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

    private function deleteTeam(int $teamId)
    {
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    private function createUser()
    {
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
        $responseCreateUser->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreateUser = $responseCreateUser->decodeResponseJson();
        return $contentCreateUser['data'];
    }

    private function deleteUser(int $userId)
    {
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }
}
