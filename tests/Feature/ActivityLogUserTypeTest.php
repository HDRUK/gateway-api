<?php

namespace Tests\Feature;

use Config;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogUserTypeTest extends TestCase
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
     * List all ActivityLogUserType
     * 
     * @return void
     */
    public function test_the_application_can_list_activity_log_user_types()
    {
        $response = $this->get('api/v1/activity_log_user_types', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'name',
                    ],
                ],
            ]);
    }

    /**
     * Tests that an ActivityLogUserType can be listed by id
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_activity_log_user_type()
    {
        $response = $this->json(
            'POST',
            'api/v1/activity_log_user_types',
            [
                'name' => 'test log user type',
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

        $response = $this->get('api/v1/activity_log_user_types/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'name',
                ],
            ]);
    }

    /**
     * Tests that an ActivityLogUserType can be created
     * 
     * @return void
     */
    public function test_the_application_can_create_an_activity_log_user_type()
    {
        $response = $this->json(
            'POST',
            'api/v1/activity_log_user_types',
            [
                'name' => 'test activity log user type',
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
     * Tests it can update an ActivityLogUserType
     * 
     * @return void
     */
    public function test_the_application_can_update_an_activity_log_user_types()
    {
        // Start by creating a new activity log user type record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/activity_log_user_types',
            [
                'name' => 'test activity log user type',
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

        // Finally, update the last entered activity log user type to
        // prove functionality
        $response = $this->json(
            'PATCH',
            'api/v1/activity_log_user_types/' . $content['data'],
            [
                'name' => 'updated activity log user type'
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $content = $response->decodeResponseJson();
        var_dump($content);
        
        $this->assertEquals($content['data']['name'], 'updated activity log user type');
    }

    /**
     * Tests it can delete an ActivityLogUserType
     * 
     * @return void
     */
    public function test_it_can_delete_an_activity_log_user_type()
    {
        // Start by creating a new activity log user type record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/activity_log_user_types',
            [
                'name' => 'to be deleted',
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

        // Finally, delete the last entered activity log user type to
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/activity_log_user_types/' . $content['data'],
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