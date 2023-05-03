<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FilterTest extends TestCase
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
     * List all filters.
     *
     * @return void
     */
    public function test_the_application_can_list_filters()
    {
        $response = $this->get('api/v1/filters', [
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
                        'type',
                        'value',
                        'keys',
                        'enabled',
                    ],
                ],
            ]);

    }

    /**
     * Returns a single filter
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_filter()
    {
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'value' => 'Some value here',
                'keys' => 'purpose',
                'enabled' => 0,
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

        $response = $this->get('api/v1/filters/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'type',
                    'value',
                    'keys',
                    'enabled',
                ],
            ]);

    }

    /**
     * Creates a new filter
     * 
     * @return void
     */
    public function test_the_application_can_create_a_filter()
    {
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'value' => 'Some value here',
                'keys' => 'purpose',
                'enabled' => 0,
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
     * Tests that a filter record can be updated
     * 
     * @return void
     */
    public function test_the_application_can_update_a_filter()
    {
        // Start by creating a new filter record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'value' => 'Initial Value',
                'keys' => 'purpose',
                'enabled' => 0,
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

        // Finally, update the last entered filter to 
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/filters/' . $content['data'],
            [
                'type' => 'project',
                'value' => 'New Value',
                'keys' => 'purpose',
                'enabled' => 1,
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
        $this->assertEquals($content['data']['value'], 'New Value');
        $this->assertEquals($content['data']['enabled'], 1);
    }

    /**
     * Tests it can delete a filter
     * 
     * @return void
     */
    public function test_it_can_delete_a_filter()
    {
        // Start by creating a new filter record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'value' => 'Initial Value',
                'keys' => 'purpose',
                'enabled' => 0,
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

        // Finally, update the last entered filter to 
        // prove functionality        
        $response = $this->json(
            'DELETE',
            'api/v1/filters/' . $content['data'],
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
