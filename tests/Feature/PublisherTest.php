<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;

class PublisherTest extends TestCase
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
     * List all publishers.
     *
     * @return void
     */
    public function test_the_application_can_list_publishers()
    {
        $response = $this->get('api/v1/publishers', [
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
                        'name',
                        'enabled',
                        'allows_messaging',
                        'workflow_enabled',
                        'uses_5_safes',
                        'member_of',
                        'contact_point',
                        'application_form_updated_by',
                        'application_form_updated_on',
                    ],
                ],
            ]);
    }

    /**
     * List a particular publisher.
     *
     * @return void
     */
    public function test_the_application_can_show_one_publisher()
    {
        $response = $this->get('api/v1/publishers/1', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                'id',
                'created_at',
                'updated_at',
                'deleted_at',
                'name',
                'enabled',
                'allows_messaging',
                'workflow_enabled',
                'uses_5_safes',
                'member_of',
                'contact_point',
                'application_form_updated_by',
                'application_form_updated_on',
            ],
        ]);        
    }

    /**
     * Create a new publisher.
     *
     * @return void
     */
    public function test_the_application_can_create_a_publisher()
    {
        $response = $this->json(
            'POST', 
            'api/v1/publishers', 
            [  
                'name' => 'A. Test Publisher', 
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
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
    }

    /**
     * Update an existing publisher.
     *
     * @return void
     */
    public function test_the_application_can_update_a_publisher()
    {
        // First create a publisher for us to update within this
        // test
        $response = $this->json(
            'POST', 
            'api/v1/publishers', 
            [  
                'name' => 'Created Test Publisher', 
                'enabled' => 0,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
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
                
        // Finally, update this publisher with new details
        $response = $this->json(
            'PATCH', 
            'api/v1/publishers/' . $content['data'],
            [  
                'name' => 'Updated Test Publisher', 
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 0,
                'member_of' => 1002,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:45:41',
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

        $this->assertEquals($content['data']['enabled'], 1);
        $this->assertEquals($content['data']['member_of'], 1002);
        $this->assertEquals($content['data']['name'], 'Updated Test Publisher');
    }

    /**
     * Delete a publisher.
     *
     * @return void
     */
    public function test_the_application_can_delete_a_publisher()
    {
        // First create a publisher for us to delete within this
        // test
        $response = $this->json(
            'POST', 
            'api/v1/publishers', 
            [  
                'name' => 'Deletable Test Publisher', 
                'enabled' => 0,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
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

        // Finally, delete the publisher we just created
        $response = $this->json(
            'DELETE', 
            'api/v1/publishers/' . $content['data'], 
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );     

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }
}
