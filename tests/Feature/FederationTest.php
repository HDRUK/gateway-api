<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Federation;
use App\Http\Enums\TeamMemberOf;
use App\Models\TeamHasFederation;
use Tests\Traits\MockExternalApis;
use App\Models\FederationHasNotification;

class FederationTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public const TEST_URL_NOTIFICATION = 'api/v1/notifications';
    public const TEST_URL_TEAM = 'api/v1/teams';

    public function setUp(): void
    {
        $this->commonSetUp();
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
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            self::TEST_URL_TEAM . '/' . $teamId . '/federations',
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '07',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3'
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
        $responseGetFederation = $this->get(self::TEST_URL_TEAM . '/' . $teamId . '/federations', $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'federation_type',
                    'auth_type',
                    'auth_secret_key_location',
                    'endpoint_baseurl',
                    'endpoint_datasets',
                    'endpoint_dataset',
                    'run_time_hour',
                    'run_time_minute',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'tested',
                    'notifications',
                ],
            ],
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
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            self::TEST_URL_TEAM . '/' . $teamId . '/federations',
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret-abc',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '07',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
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
        $responseGetFederation = $this->get(self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'federation_type',
                'auth_type',
                'auth_secret_key_location',
                'endpoint_baseurl',
                'endpoint_datasets',
                'endpoint_dataset',
                'run_time_hour',
                'run_time_minute',
                'enabled',
                'created_at',
                'updated_at',
                'deleted_at',
                'tested',
                'notifications',
            ],
        ]);

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
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            self::TEST_URL_TEAM . '/' . $teamId . '/federations',
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '07',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
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
        $responseGetFederation = $this->get(self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'federation_type',
                'auth_type',
                'auth_secret_key_location',
                'endpoint_baseurl',
                'endpoint_datasets',
                'endpoint_dataset',
                'run_time_hour',
                'run_time_minute',
                'enabled',
                'created_at',
                'updated_at',
                'deleted_at',
                'tested',
                'notifications',
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
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
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
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            self::TEST_URL_TEAM . '/' . $teamId . '/federations',
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '07',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
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
        $responseGetFederation = $this->get(self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'federation_type',
                'auth_type',
                'auth_secret_key_location',
                'endpoint_baseurl',
                'endpoint_datasets',
                'endpoint_dataset',
                'run_time_hour',
                'run_time_minute',
                'enabled',
                'created_at',
                'updated_at',
                'deleted_at',
                'tested',
                'notifications',
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
            self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId,
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path/test',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '02',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
                    '4',
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
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
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
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            self::TEST_URL_TEAM . '/' . $teamId . '/federations',
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '02',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
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
        $responseGetFederation = $this->get(self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'federation_type',
                'auth_type',
                'auth_secret_key_location',
                'endpoint_baseurl',
                'endpoint_datasets',
                'endpoint_dataset',
                'run_time_hour',
                'run_time_minute',
                'enabled',
                'created_at',
                'updated_at',
                'deleted_at',
                'tested',
                'notifications',
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
            self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId,
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path/test',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '02',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
                    '4',
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
            self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId,
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path/test/update',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app/update',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '02',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
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
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $response = $this->json(
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $response->decodeResponseJson();
        $teamId = $content['data'];

        // create federation for team
        $responseFederation = $this->json(
            'POST',
            self::TEST_URL_TEAM . '/' . $teamId . '/federations',
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '02',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
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
        $responseGetFederation = $this->get(self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId, $this->header);

        $responseGetFederation->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'federation_type',
                'auth_type',
                'auth_secret_key_location',
                'endpoint_baseurl',
                'endpoint_datasets',
                'endpoint_dataset',
                'run_time_hour',
                'run_time_minute',
                'enabled',
                'created_at',
                'updated_at',
                'deleted_at',
                'tested',
                'notifications',
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
            self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId,
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path/test',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '02',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
                    '3',
                    '4',
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
            self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId,
            [
                'federation_type' => 'federation type',
                'auth_type' => 'BEARER',
                'auth_secret_key' => 'secret/key/path/test/update',
                'endpoint_baseurl' => 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app/update',
                'endpoint_datasets' => '/api/v1/noauth/datasets',
                'endpoint_dataset' => '/api/v1/noauth/datasets/{id}',
                'run_time_hour' => 11,
                'run_time_minute' => '02',
                'enabled' => true,
                'notifications' => [
                    '1',
                    '2',
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

        $this->assertTrue($federations->endpoint_baseurl === 'https://fma-custodian-test-server-pljgro4dzq-nw.a.run.app/update');

        // delete
        $responseDeleteFederation = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '/federations/' . $federationId,
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
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);
    }
}
