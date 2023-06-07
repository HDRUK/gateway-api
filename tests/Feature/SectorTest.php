<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SectorTest extends TestCase
{
    use RefreshDatabase;
    private $accessToken = '';    

    public function setUp() :void
    {
        parent::setUp();

        $this->seed();

        $response = $this->postJson('api/v1/auth', [
            'email' => 'developers@hdruk.ac.uk',
            'password' => 'Watch26Task?',
        ]);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $content = $response->decodeResponseJson();  
        $this->accessToken = $content['access_token'];      
    }

    /**
     * List all sectors.
     *
     * @return void
     */
    public function test_the_application_can_list_sectors()
    {
        $response = $this->get('api/v1/sectors', [
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
                        'name',
                        'enabled',
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
     * Returns a single sector
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_sector()
    {
        $response = $this->json(
            'POST',
            'api/v1/sectors',
            [
                'name' => 'Test Sector',
                'enabled' => false,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/sectors/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'name',
                    'enabled',
                ],
            ]);

    }

    /**
     * Creates a new sector
     * 
     * @return void
     */
    public function test_the_application_can_create_a_sector()
    {
        $response = $this->json(
            'POST',
            'api/v1/sectors',
            [
                'name' => 'Test Sector',
                'enabled' => false,
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
    }

    /**
     * Tests that a sector record can be updated
     * 
     * @return void
     */
    public function test_the_application_can_update_a_sector()
    {
        // Start by creating a new sector record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/sectors',
            [
                'name' => 'Test Sector',
                'enabled' => false,
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

        // Finally, update the last entered sector to 
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/sectors/' . $content['data'],
            [
                'name' => 'Updated Test Sector',
                'enabled' => true,
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
        $this->assertEquals($content['data']['name'], 'Updated Test Sector');
        $this->assertEquals($content['data']['enabled'], true);
    }

    /**
     * Tests it can delete a sector
     * 
     * @return void
     */
    public function test_it_can_delete_a_sector()
    {
        // Start by creating a new sector record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/sectors',
            [
                'name' => 'Test Sector',
                'enabled' => false,
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

        // Finally, update the last entered sector to 
        // prove functionality        
        $response = $this->json(
            'DELETE',
            'api/v1/sectors/' . $content['data'],
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
            Config::get('statuscodes.STATUS_OK.message')
        );
    }
}
