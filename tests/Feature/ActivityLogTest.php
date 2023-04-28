<?php

namespace Tests\Feature;

use Config;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
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
     * List all ActicityLogs
     * 
     * @return void
     */
    public function test_the_application_can_list_activity_logs()
    {
        $response = $this->get('api/v1/activity_logs', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'event_type',
                        'user_type_id',
                        'log_type_id',
                        'user_id',
                        'version',
                        'html',
                        'plain_text',
                        'user_id_mongo',
                        'version_id_mongo',
                    ],
                ],
            ]);
    }

    /**
     * Tests that an activity log can be listed by id
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_activity_log()
    {
        $response = $this->json(
            'POST',
            'api/v1/activity_logs',
            [
                'event_type' => 'test_case',
                'user_type_id' => 1,
                'log_type_id' => 1,
                'user_id' => 1,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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

        $response = $this->get('api/v1/activity_logs/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'event_type',
                    'user_type_id',
                    'log_type_id',
                    'user_id',
                    'version',
                    'html',
                    'plain_text',
                    'user_id_mongo',
                    'version_id_mongo',
                ],
            ]);
    }

    /**
     * Tests that an activity log can be created
     * 
     * @return void
     */
    public function test_the_application_can_create_an_activity_log()
    {
        $response = $this->json(
            'POST',
            'api/v1/activity_logs',
            [
                'event_type' => 'test_case',
                'user_type_id' => 1,
                'log_type_id' => 1,
                'user_id' => 1,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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
     * Tests it can update an activity log
     * 
     * @return void
     */
    public function test_the_application_can_update_an_activity_log()
    {
        // Start by creating a new activity log record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/activity_logs',
            [
                'event_type' => 'test_case',
                'user_type_id' => 1,
                'log_type_id' => 1,
                'user_id' => 1,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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

        // Finally, update the last entered activity log to
        // prove functionality
        $response = $this->json(
            'PATCH',
            'api/v1/activity_logs/' . $content['data'],
            [
                'event_type' => 'updated_test_case',
                'user_type_id' => 2,
                'log_type_id' => 2,
                'user_id' => 2,
                'version' => '1.0.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $content = $response->decodeResponseJson();
        
        $this->assertEquals($content['data']['event_type'], 'updated_test_case');
        $this->assertEquals($content['data']['user_type_id'], 2);
        $this->assertEquals($content['data']['log_type_id'], 2);
        $this->assertEquals($content['data']['user_id'], 2);
        $this->assertEquals($content['data']['version'], '1.0.0');
    }

    /**
     * Tests it can delete an activity log
     * 
     * @return void
     */
    public function test_it_can_delete_an_activity_log()
    {
        // Start by creating a new activity log record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/activity_logs',
            [
                'event_type' => 'test_case',
                'user_type_id' => 1,
                'log_type_id' => 1,
                'user_id' => 1,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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

        // Finally, delete the last entered activity log to
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/activity_logs/' . $content['data'],
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