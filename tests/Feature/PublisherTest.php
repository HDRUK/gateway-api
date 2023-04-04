<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherTest extends TestCase
{
    private $accessToken = '';

    public function setUp() :void
    {
        parent::setUp();

        $response = $this->postJson('api/v1/auth', [
            'email' => 'trex@mail.com',
            'password' => 'roar123!',
        ]);
        $response->assertStatus(200);

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

        $response->assertStatus(200);
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

        $response->assertStatus(200);
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
                'member_of' => 'The Jurassic org',
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 564757,
                'application_form_updated_on' => '2023-04-06 15:44:41',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(200);
    }

    /**
     * Update an existing publisher.
     *
     * @return void
     */
    public function test_the_application_can_update_a_publisher()
    {    
        $response = $this->json(
            'PATCH', 
            'api/v1/publishers/51',
            [  
                'name' => 'A. Test Publisher', 
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'member_of' => 'The Cretaceous org',
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 564757,
                'application_form_updated_on' => '2023-04-06 15:45:41',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(200);
    }

    /**
     * Delete a publisher.
     *
     * @return void
     */
    public function test_the_application_can_delete_a_publisher()
    {
        $response = $this->json(
            'DELETE',
            'api/v1/publishers/51',
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(200);
    }
}
