<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterTest extends TestCase
{
    private $accessToken = '';

    public function setUp() :void
    {
        parent::setUp();

        $response = $this->postJson('api/v1/auth', [
            'email' => 'developers@hdruk.ac.uk',
            'password' => 'Watch26Task?',
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
     * List all filters.
     *
     * @return void
     */
    public function test_the_application_can_list_filters()
    {
        $response = $this->get('api/v1/filters', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(200)
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
        $response = $this->get('api/v1/filters/3', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        
        $response->assertStatus(200)
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

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], 'success');
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

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], 'success');

        // Finally, update the last entered filter to 
        // prove functionality
        $response = $this->json(
            'PATCH',
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

        $response->assertStatus(200)
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
        $response = $this->delete('api/v1/filters/1', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        dd($response);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);
    

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], 'success');
    }
}
