<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Federation;
use Tests\Traits\Authorization;
use App\Models\TeamHasFederation;
use App\Models\FederationHasNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FederationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    protected $header = [];
    protected $accessToken = null;

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    /**
     * Get All Federations By Team ID with success
     * 
     * @return void
     */
    public function test_get_all_federation_by_team_id_with_success(): void
    {
        // create a new team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
            'POST',
            'api/v1/teams',
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

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/federations',
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    't1@test.com',
                    't2@test.com',
                    't3@test.com'
                ]
            ],
            $this->header,
        );

        $responseFederation->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentFederation = $responseFederation->decodeResponseJson();
        $federationId = $contentFederation['data'];

        // get federation by team id
        $responseGetFederation = $this->get('api/v1/teams/' . $teamId . '/federations', $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'name',
                    'enabled',
                    'allows_messaging',
                    'workflow_enabled',
                    'access_requests_management',
                    'uses_5_safes',
                    'is_admin',
                    'member_of',
                    'contact_point',
                    'application_form_updated_by',
                    'application_form_updated_on',
                    'mdm_folder_id',
                    'federation',
                ],
            ],
        ]);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            'api/v1/teams' . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);
    }

    /**
     * Get FederationsBy Federation Id & Team ID with success
     * 
     * @return void
     */
    public function test_get_federation_by_id_and_by_team_id_with_success(): void
    {
        // create a new team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
            'POST',
            'api/v1/teams',
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

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/federations',
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    't1@test.com',
                    't2@test.com',
                    't3@test.com'
                ]
            ],
            $this->header,
        );

        $responseFederation->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentFederation = $responseFederation->decodeResponseJson();
        $federationId = $contentFederation['data'];

        // get federation by id and by team id
        $responseGetFederation = $this->get('api/v1/teams/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'name',
                    'enabled',
                    'allows_messaging',
                    'workflow_enabled',
                    'access_requests_management',
                    'uses_5_safes',
                    'is_admin',
                    'member_of',
                    'contact_point',
                    'application_form_updated_by',
                    'application_form_updated_on',
                    'mdm_folder_id',
                    'federation',
                ],
            ],
        ]);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            'api/v1/teams' . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);
    }

    /**
     * Create FederationsBy by Team ID with success
     * 
     * @return void
     */
    public function test_create_federation_by_team_id_with_success(): void
    {
        // create a new team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
            'POST',
            'api/v1/teams',
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

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/federations',
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    't1@test.com',
                    't2@test.com',
                    't3@test.com'
                ]
            ],
            $this->header,
        );

        $responseFederation->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentFederation = $responseFederation->decodeResponseJson();
        $federationId = $contentFederation['data'];

        // get federation by id and by team id
        $responseGetFederation = $this->get('api/v1/teams/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'name',
                    'enabled',
                    'allows_messaging',
                    'workflow_enabled',
                    'access_requests_management',
                    'uses_5_safes',
                    'is_admin',
                    'member_of',
                    'contact_point',
                    'application_form_updated_by',
                    'application_form_updated_on',
                    'mdm_folder_id',
                    'federation',
                ],
            ],
        ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(3, $federationNotification, 'Response was successfully');

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            'api/v1/teams' . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);
    }

    /**
     * Update Federations by Federation Id & Team ID with success
     * 
     * @return void
     */
    public function test_update_federation_by_federation_id_and_team_id_with_success(): void
    {
        // create a new team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
            'POST',
            'api/v1/teams',
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

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/federations',
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    't1@test.com',
                    't2@test.com',
                    't3@test.com'
                ]
            ],
            $this->header,
        );

        $responseFederation->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentFederation = $responseFederation->decodeResponseJson();
        $federationId = $contentFederation['data'];

        // get federation by id and by team id
        $responseGetFederation = $this->get('api/v1/teams/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'name',
                    'enabled',
                    'allows_messaging',
                    'workflow_enabled',
                    'access_requests_management',
                    'uses_5_safes',
                    'is_admin',
                    'member_of',
                    'contact_point',
                    'application_form_updated_by',
                    'application_form_updated_on',
                    'mdm_folder_id',
                    'federation',
                ],
            ],
        ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(3, $federationNotification, 'Response was successfully');

        // update federation
        $responseUpdateFederation = $this->json(
            'PUT',
            'api/v1/teams/' . $teamId . '/federations/' . $federationId,
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path/test',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    'y1@test.com',
                    'y2@test.com',
                    'y3@test.com',
                    'y4@test.com'
                ]
            ],
            $this->header,
        );

        $responseUpdateFederation->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(4, $federationNotification, 'Response was successfully');

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            'api/v1/teams' . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);
    }

    /**
     * Edit Federations by Federation Id & Team ID with success
     * 
     * @return void
     */
    public function test_edit_federation_by_federation_id_and_team_id_with_success(): void
    {
        // create a new team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
            'POST',
            'api/v1/teams',
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

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/federations',
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    't1@test.com',
                    't2@test.com',
                    't3@test.com'
                ]
            ],
            $this->header,
        );

        $responseFederation->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentFederation = $responseFederation->decodeResponseJson();
        $federationId = $contentFederation['data'];

        // get federation by id and by team id
        $responseGetFederation = $this->get('api/v1/teams/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'name',
                    'enabled',
                    'allows_messaging',
                    'workflow_enabled',
                    'access_requests_management',
                    'uses_5_safes',
                    'is_admin',
                    'member_of',
                    'contact_point',
                    'application_form_updated_by',
                    'application_form_updated_on',
                    'mdm_folder_id',
                    'federation',
                ],
            ],
        ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(3, $federationNotification, 'Response was successfully');

        // update federation
        $responseUpdateFederation = $this->json(
            'PUT',
            'api/v1/teams/' . $teamId . '/federations/' . $federationId,
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path/test',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    'y1@test.com',
                    'y2@test.com',
                    'y3@test.com',
                    'y4@test.com'
                ]
            ],
            $this->header,
        );

        $responseUpdateFederation->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(4, $federationNotification, 'Response was successfully');

        // edit federation
        $responseEditFederation = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId . '/federations/' . $federationId,
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path/test/update',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app/update',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    'y1@test.com',
                    'y2@test.com',
                ]
            ],
            $this->header,
        );

        $responseEditFederation->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(2, $federationNotification, 'Response was successfully');

        $federations = Federation::where('id', $federationId)->first();

        $this->assertTrue($federations->auth_secret_key === 'secret/key/path/test/update');

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            'api/v1/teams' . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);
    }


    /**
     * Delete Federations by Federation Id & Team ID with success
     * 
     * @return void
     */
    public function test_delete_federation_by_federation_id_and_team_id_with_success(): void
    {
        // create a new team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'joe@example.com',
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
            'POST',
            'api/v1/teams',
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

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/federations',
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    't1@test.com',
                    't2@test.com',
                    't3@test.com'
                ]
            ],
            $this->header,
        );

        $responseFederation->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $contentFederation = $responseFederation->decodeResponseJson();
        $federationId = $contentFederation['data'];

        // get federation by id and by team id
        $responseGetFederation = $this->get('api/v1/teams/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'name',
                    'enabled',
                    'allows_messaging',
                    'workflow_enabled',
                    'access_requests_management',
                    'uses_5_safes',
                    'is_admin',
                    'member_of',
                    'contact_point',
                    'application_form_updated_by',
                    'application_form_updated_on',
                    'mdm_folder_id',
                    'federation',
                ],
            ],
        ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(3, $federationNotification, 'Response was successfully');

        // update federation
        $responseUpdateFederation = $this->json(
            'PUT',
            'api/v1/teams/' . $teamId . '/federations/' . $federationId,
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path/test',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    'y1@test.com',
                    'y2@test.com',
                    'y3@test.com',
                    'y4@test.com'
                ]
            ],
            $this->header,
        );

        $responseUpdateFederation->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(4, $federationNotification, 'Response was successfully');

        // edit federation
        $responseEditFederation = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId . '/federations/' . $federationId,
            [
                'auth_type' => 'oauth',
                'auth_secret_key' => 'secret/key/path/test/update',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app/update',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'enabled' => true,
                'notification' => [
                    'y1@test.com',
                    'y2@test.com',
                ]
            ],
            $this->header,
        );

        $responseEditFederation->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertTrue((bool) $federationNotification, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->get()->toArray();

        $this->assertCount(2, $federationNotification, 'Response was successfully');

        $federations = Federation::where('id', $federationId)->first();

        $this->assertTrue($federations->auth_secret_key === 'secret/key/path/test/update');
        $this->assertTrue($federations->endpoint_baseurl === 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app/update');

        // delete
        $responseDeleteFederation = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '/federations/' . $federationId,
            [],
            $this->header,
        );

        $responseDeleteFederation->assertStatus(200)
        ->assertJsonStructure([
            'message',
        ]);

        $teamFederation = TeamHasFederation::where([
            'team_id' => $teamId,
            'federation_id' => $federationId,
        ])->first();

        $this->assertFalse((bool) $teamFederation, 'Response was successfully');

        $federationNotification = FederationHasNotification::where([
            'federation_id' => $federationId,
        ])->first();

        $this->assertFalse((bool) $federationNotification, 'Response was successfully');

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            'api/v1/teams' . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);
    }
}
