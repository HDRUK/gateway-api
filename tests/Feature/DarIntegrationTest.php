<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;

class DarIntegrationTest extends TestCase
{
    private $accessToken = '';

    public function setUp() :void
    {
        parent::setUp();

        $response = $this->postJson('api/v1/auth', [
            'email' => 'developers@hdruk.ac.uk',
            'password' => 'Watch26Task?',
        ]);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $content = $response->decodeResponseJson();  
        $this->accessToken = $content['access_token'];      
    }

    public function tearDown() :void
    {
        parent::tearDown();
        $this->accessToken = null;
    }

    /**
     * List all DARs.
     *
     * @return void
     */
    public function test_the_application_can_list_dars()
    {
        $response = $this->get('api/v1/dar-integrations', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'enabled',
                        'notification_email',
                        'outbound_auth_type',
                        'outbound_auth_key',
                        'outbound_endpoints_base_url',
                        'outbound_endpoints_enquiry',
                        'outbound_endpoints_5safes',
                        'outbound_endpoints_5safes_files',
                        'inbound_service_account_id',
                    ],
                ],
            ]);

    }

    /**
     * Returns a single DAR
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_dar()
    {
        $response = $this->get('api/v1/dar-integrations/1', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'enabled',
                    'notification_email',
                    'outbound_auth_type',
                    'outbound_auth_key',
                    'outbound_endpoints_base_url',
                    'outbound_endpoints_enquiry',
                    'outbound_endpoints_5safes',
                    'outbound_endpoints_5safes_files',
                    'inbound_service_account_id',
                ],
            ]);

    }

    /**
     * Creates a new DAR
     * 
     * @return void
     */
    public function test_the_application_can_create_a_dar()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar-integrations',
            [
                'enabled' => 1,
                'notification_email' => 'someone@somewhere.com',
                'outbound_auth_type' => 'auth_type_123',
                'outbound_auth_key' => 'auth_key_456',
                'outbound_endpoints_base_url' => 'https://something.com/',
                'outbound_endpoints_enquiry' => 'enquiry',
                'outbound_endpoints_5safes' => '5safes',
                'outbound_endpoints_5safes_files' => '5safes-files',
                'inbound_service_account_id' => '1234567890',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], 
            Config::get('statuscodes.STATUS_CREATED.message'));
    }

    /**
     * Tests that a DAR record can be updated
     * 
     * @return void
     */
    public function test_the_application_can_update_a_dar()
    {
        // Start by creating a new DAR record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/dar-integrations',
            [
                'enabled' => 1,
                'notification_email' => 'someone@somewhere.com',
                'outbound_auth_type' => 'auth_type_123',
                'outbound_auth_key' => 'auth_key_456',
                'outbound_endpoints_base_url' => 'https://something.com/',
                'outbound_endpoints_enquiry' => 'enquiry',
                'outbound_endpoints_5safes' => '5safes',
                'outbound_endpoints_5safes_files' => '5safes-files',
                'inbound_service_account_id' => '1234567890',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, update the last entered DAR to 
        // prove functionality
        $response = $this->json(
            'PATCH',
            'api/v1/dar-integrations/' . $content['data'],
            [
                'enabled' => 1,
                'notification_email' => 'someone.else@somewhere-else.com',
                'outbound_auth_type' => 'auth_type_234',
                'outbound_auth_key' => 'auth_key_123',
                'outbound_endpoints_base_url' => 'https://something.com/',
                'outbound_endpoints_enquiry' => 'enquiry',
                'outbound_endpoints_5safes' => '5safes',
                'outbound_endpoints_5safes_files' => '5safes-files',
                'inbound_service_account_id' => '0987654321',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['notification_email'], 'someone.else@somewhere-else.com');
        $this->assertEquals($content['data']['outbound_auth_type'], 'auth_type_234');
        $this->assertEquals($content['data']['outbound_auth_key'], 'auth_key_123');
        $this->assertEquals($content['data']['inbound_service_account_id'], '0987654321');
    }

    /**
     * Tests it can delete a DAR
     * 
     * @return void
     */
    public function test_it_can_delete_a_dar()
    {
        // Start by creating a new DAR record for deleting
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/dar-integrations',
            [
                'enabled' => 1,
                'notification_email' => 'someone@somewhere.com',
                'outbound_auth_type' => 'auth_type_123',
                'outbound_auth_key' => 'auth_key_456',
                'outbound_endpoints_base_url' => 'https://something.com/',
                'outbound_endpoints_enquiry' => 'enquiry',
                'outbound_endpoints_5safes' => '5safes',
                'outbound_endpoints_5safes_files' => '5safes-files',
                'inbound_service_account_id' => '1234567890',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, delete the last entered DAR to 
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/dar-integrations/' . $content['data'],
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            Config::get('statuscodes.STATUS_OK.message'));
    }
}
