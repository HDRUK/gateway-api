<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Database\Seeders\DarIntegrationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DarIntegrationTest extends TestCase
{
    use RefreshDatabase;
    private $accessToken = '';

    public function setUp() :void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            DarIntegrationSeeder::class,
        ]
    );

        $response = $this->postJson('api/v1/auth', [
            'email' => 'developers@hdruk.ac.uk',
            'password' => 'Watch26Task?',
        ]);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $content = $response->decodeResponseJson();  
        $this->accessToken = $content['access_token'];      
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
                'current_page',
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

    }

    /**
     * Returns a single DAR
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_dar()
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
        
        $response = $this->get('api/v1/dar-integrations/' . $content['data'], [
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
            'PUT',
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
     * Tests that a DAR record can be updated
     * 
     * @return void
     */
    public function test_the_application_can_edit_a_dar()
    {
        // create
        $responseCreate = $this->json(
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

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals($contentCreate['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            'api/v1/dar-integrations/' . $id,
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

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['notification_email'], 'someone.else@somewhere-else.com');
        $this->assertEquals($contentUpdate['data']['outbound_auth_type'], 'auth_type_234');
        $this->assertEquals($contentUpdate['data']['outbound_auth_key'], 'auth_key_123');
        $this->assertEquals($contentUpdate['data']['inbound_service_account_id'], '0987654321');

        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/dar-integrations/' . $id,
            [
                'outbound_auth_type' => 'auth_type_234_e1',
                'outbound_auth_key' => 'auth_key_123_e1',
                'outbound_endpoints_base_url' => 'https://something.e1.com/',
                'outbound_endpoints_enquiry' => 'enquiry_e1',
                'outbound_endpoints_5safes' => '5safes_e1',
                'outbound_endpoints_5safes_files' => '5safes-files-e1',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['outbound_auth_type'], 'auth_type_234_e1');
        $this->assertEquals($contentEdit1['data']['outbound_auth_key'], 'auth_key_123_e1');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_base_url'], 'https://something.e1.com/');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_enquiry'], 'enquiry_e1');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_5safes'], '5safes_e1');
        $this->assertEquals($contentEdit1['data']['outbound_endpoints_5safes_files'], '5safes-files-e1');

        // Edit
        $responseEdit2 = $this->json(
            'PATCH',
            'api/v1/dar-integrations/' . $id,
            [
                'notification_email' => 'someone.else@somewhere-e2.com',
                'outbound_auth_type' => 'auth_type_234_e2',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseEdit2->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();
        $this->assertEquals($contentEdit2['data']['notification_email'], 'someone.else@somewhere-e2.com');
        $this->assertEquals($contentEdit2['data']['outbound_auth_type'], 'auth_type_234_e2');
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
