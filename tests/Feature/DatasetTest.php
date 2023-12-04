<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use Database\Seeders\SectorSeeder;
use Tests\Traits\Authorization;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use MetadataManagementController AS MMC;
use Mockery;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Nyholm\Psr7\Response;

class DatasetTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL_DATASET = '/api/v1/datasets';
    const TEST_URL_TEAM = 'api/v1/teams';
    const TEST_URL_NOTIFICATION = 'api/v1/notifications';
    const TEST_URL_USER = 'api/v1/users';

    private $dataset = null;
    private $datasetUpdate = null;

    protected $header = [];
    

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SectorSeeder::class,
        ]);
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        // Lengthy process, but a more consistent representation
        // of an incoming dataset
        $this->dataset = $this->getFakeDataset();
        $this->datasetUpdate = $this->getFakeUpdateDataset();

        // Define mock client and fake response for elasticsearch service
        $mock = new Client();

        $client = ClientBuilder::create()
            ->setHttpClient($mock)
            ->build();

        // This is a PSR-7 response
        // Mock two responses, one for creating a dataset, another for deleting
        $createResponse = new Response(
            200, 
            [Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            'Document created'
        );
        $deleteResponse = new Response(
            200, 
            [Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            'Document deleted'
        );

        // Stack the responses expected in the create/archive/delete dataset test
        // create -> soft delete/archive -> unarchive -> permanent delete
        for ($i=0; $i < 100; $i++) {
            $mock->addResponse($createResponse);
        }

        for ($i=0; $i < 100; $i++) {
            $mock->addResponse($deleteResponse);
        }

        $this->testElasticClient = $client;
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

        Http::fake([
            'ted*' => Http::response(
                ['id' => 11, 'extracted_terms' => ['test', 'fake']], 
                201,
                ['application/json']
            )
        ]);
        
        // Mock the MMC getElasticClient method to return the mock client
        // makePartial so other MMC methods are not mocked
        MMC::shouldReceive('getElasticClient')->andReturn($this->testElasticClient);
        MMC::makePartial();

        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => 'Some@email.com',
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
                'orcid' =>" https://orcid.org/75697342",
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
        $labelDataset1 = 'XYZ DATASET';
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId1,
                'user_id' => $userId,
                'label' => $labelDataset1,
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->dataset,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $datasetId1 = $responseCreateDataset['data'];

        //create a 2nd one
        $labelDataset2 = 'ABC DATASET';
        $responseCreateDataset2 = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId1,
                'user_id' => $userId,
                'label' => $labelDataset2,
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->dataset,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $responseCreateDataset2->assertStatus(201);
        $datasetId2 = $responseCreateDataset2['data'];

        //create a 3rd one which is owned by the 2nd team
        $labelDataset3 = 'Other Team DATASET';
        $responseCreateDataset3 = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId2,
                'user_id' => $userId,
                'label' => $labelDataset3,
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->dataset,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $responseCreateDataset3->assertStatus(201);
        $datasetId3 = $responseCreateDataset3['data'];


        $response = $this->json('GET', self::TEST_URL_DATASET,
                                       [], $this->header
                                    );
        $response->assertStatus(200);
        $this->assertCount(3,$response['data']);
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
        * Test filtering by dataset title and status
        */
        $responseStatus = $this->json('GET', self::TEST_URL_DATASET . 
            '?title=HDR&status=DRAFT',
            [], $this->header
        );
        $responseStatus->assertStatus(200);
        //should find the two draft datasets, whose titles both contain HDR
        $this->assertCount(2,$responseStatus['data']);

        /* 
        * Sort so that the newest dataset is first in the list
        */
        $response = $this->json('GET', self::TEST_URL_DATASET . 
                                        '?team_id=' . $teamId1 . 
                                        '&sort=created:desc',
                                        [], $this->header
        );
        $first = Carbon::parse($response['data'][0]['created']);
        $second = Carbon::parse($response['data'][1]['created']);

        $this->assertTrue($first->gt($second));

        /*
        * use the endpoint /api/v1/datasets/count to found unique values of the field 'status'
        */
        $responseCount = $this->json('GET', self::TEST_URL_DATASET . 
                                            '/count/status?team_id=' . $teamId1 ,
                                            [], $this->header
        );
        $responseCount->assertStatus(200);
        $countActive = $responseCount['data']['ACTIVE'];
        $countDraft = $responseCount['data']['DRAFT'];
        $this->assertTrue($countActive===1);
        $this->assertTrue($countDraft===1);
        
        /* 
        * reverse this sorting
        */
        $response = $this->json('GET', self::TEST_URL_DATASET . 
                                        '?team_id=' . $teamId1 . 
                                        '&sort=created:asc',
                                        [], $this->header
        );
        $first = Carbon::parse($response['data'][0]['created']);
        $second = Carbon::parse($response['data'][1]['created']);

        $this->assertTrue($first->lt($second));

        /* 
        * Sort A-Z on the dataset label
        */
        $response = $this->json('GET', self::TEST_URL_DATASET . 
                                        '?team_id=' . $teamId1 . 
                                        '&sort=label:asc',
                                        [], $this->header
        );
        $this->assertTrue($response['data'][0]['label'] === $labelDataset2);

        /* 
        * Sort Z-A on the dataset label
        */
        $response = $this->json('GET', self::TEST_URL_DATASET . 
                                        '?team_id=' . $teamId1 . 
                                        '&sort=label:desc',
                                        [], $this->header
        );
        $this->assertTrue($response['data'][0]['label'] === $labelDataset1);


        /* 
        * Sort Z-A on the metadata title
        */
        $response = $this->json('GET', self::TEST_URL_DATASET . 
                                        '?team_id=' . $teamId1 . 
                                        '&sort=properties/summary/title:desc',
                                        [], $this->header
        );
        $this->assertTrue($response['data'][0]['label'] === $labelDataset1);


        /* 
        * fail if a bad direction has been given for sorting
        */
        $response = $this->json('GET', self::TEST_URL_DATASET . 
                                        '?team_id=' . $teamId1 . 
                                        '&sort=created:blah',
                                        [], $this->header
        );
        $response->assertStatus(400);


        $response = $this->json('GET', self::TEST_URL_DATASET . 
            '?title=HDR&status=DRAFT',
            [], $this->header
        );


        for ($i = 1; $i <= 3; $i++) {
            // delete dataset
            $responseDeleteDataset = $this->json(
                'DELETE',
                self::TEST_URL_DATASET . '/' . ${'datasetId' . $i} . '?deletePermanently=true',
                [],
                $this->header
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
    public function test_get_one_dataset_by_id_with_success(): void
    {
        Http::fake([
            'ted*' => Http::response(
                ['id' => 11, 'extracted_terms' => ['test', 'fake']], 
                201,
                ['application/json']
            )
        ]);
        
        // Mock the MMC getElasticClient method to return the mock client
        // makePartial so other MMC methods are not mocked
        MMC::shouldReceive('getElasticClient')->andReturn($this->testElasticClient);
        MMC::makePartial();

        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => 'Some@email.com',
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
                'orcid' =>" https://orcid.org/75697342",
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
                'label' => $labelDataset,
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->dataset,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );

        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // get one dataset
        $responseGetOne = $this->json('GET', self::TEST_URL_DATASET . '/' . $datasetId, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data'
        ]);
        $responseGetOne->assertStatus(200);
        
        $respArray = $responseGetOne->decodeResponseJson();
        $this->assertArrayHasKey('named_entities', $respArray['data']);

        // delete dataset
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
     * Create/archive/unarchive Dataset with success
     * 
     * @return void
     */
    public function test_create_archive_delete_dataset_with_success(): void
    {
        Http::fake([
            'ted*' => Http::response(
                ['id' => 1111, 'extracted_terms' => ['test', 'fake']], 
                201,
                ['application/json']
            )
        ]);
        
        // Mock the MMC getElasticClient method to return the mock client
        // makePartial so other MMC methods are not mocked
        MMC::shouldReceive('getElasticClient')->andReturn($this->testElasticClient);
        MMC::makePartial();

        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => 'some@email.com',
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
                'label' => $labelDataset,
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->dataset,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // archive dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset->assertStatus(200);

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
        $responseUnarchiveDataset->assertStatus(200);

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
        $responseDeleteTeam = $this->json('DELETE',
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
        Http::fake([
            'ted*' => Http::response(
                ['id' => 1111, 'extracted_terms' => ['test', 'fake']], 
                201,
                ['application/json']
            )
        ]);
        
        // Mock the MMC getElasticClient method to return the mock client
        // makePartial so other MMC methods are not mocked
        MMC::shouldReceive('getElasticClient')->andReturn($this->testElasticClient);
        MMC::makePartial();

        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => 'some@email.com',
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
                'label' => $labelDataset,
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->dataset,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // update dataset
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId ,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'label' => $labelDataset,
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->datasetUpdate,
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
        $responseDeleteTeam = $this->json('DELETE',
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
        Http::fake([
            'ted*' => Http::response(
                ['id' => 1111, 'extracted_terms' => ['test', 'fake']], 
                201,
                ['application/json']
            )
        ]);
        
        // Mock the MMC getElasticClient method to return the mock client
        // makePartial so other MMC methods are not mocked
        MMC::shouldReceive('getElasticClient')->andReturn($this->testElasticClient);
        MMC::makePartial();

        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => 'some@email.com',
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
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
                'label' => 'label dataset ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'dataset' => $this->dataset,
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
        $responseDeleteTeam = $this->json('DELETE',
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

    private function getFakeDataset()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }

    private function getFakeUpdateDataset()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min_update.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }
}
