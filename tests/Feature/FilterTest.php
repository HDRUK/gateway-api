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
            'email' => 'laminatefish@gmail.com',
            'password' => 'Hunt32Items!',
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
}
