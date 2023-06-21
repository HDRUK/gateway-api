<?php

namespace Tests\Feature;

use Config;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogTest extends TestCase
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
     * List all AuditLog's
     * 
     * @return void
     */
    public function test_the_application_can_list_audit_logs()
    {
        $response = $this->get('api/v1/audit_logs', [
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
                        'user_id',
                        'description',
                        'function',
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
     * Tests that an AuditLog can be listed by id
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_audit_log()
    {
        $response = $this->json(
            'POST',
            'api/v1/audit_logs',
            [
                'user_id' => 1,
                'description' => 'Test audit log description',
                'function' => 'test_audit_log_creation',
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

        $response = $this->get('api/v1/audit_logs/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'user_id',
                    'description',
                    'function',
                ],
            ]);
    }

    /**
     * Tests that an AuditLog can be created
     * 
     * @return void
     */
    public function test_the_application_can_create_an_audit_log()
    {
        $response = $this->json(
            'POST',
            'api/v1/audit_logs',
            [
                'user_id' => 1,
                'description' => 'Test audit log description',
                'function' => 'test_audit_log_creation',
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
     * Tests that an AuditLog can be edit
     * 
     * @return void
     */
    public function test_the_application_can_edit_an_audit_log()
    {
        //create
        $responseCreate = $this->json(
            'POST',
            'api/v1/audit_logs',
            [
                'user_id' => 1,
                'description' => 'Test audit log description',
                'function' => 'test_audit_log_creation',
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
        $this->assertEquals($contentCreate['message'],Config::get('statuscodes.STATUS_CREATED.message'));

        $id = $contentCreate['data'];

        // edit
        $responseEdit = $this->json(
            'PATCH',
            'api/v1/audit_logs/' . $id,
            [
                'description' => 'Test audit log description edit',
                'function' => 'test_audit_log_edit',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );
        $contentEdit = $responseEdit->decodeResponseJson();
        $this->assertEquals($contentEdit['data']['id'], $id);
        $this->assertEquals($contentEdit['data']['description'], 'Test audit log description edit');
        $this->assertEquals($contentEdit['data']['function'], 'test_audit_log_edit');
        $responseEdit->assertStatus(200);
    }

    /**
     * Tests it can update an AuditLog
     * 
     * @return void
     */
    public function test_the_application_can_update_an_audit_log()
    {
        // Start by creating a new AuditLog record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/audit_logs',
            [
                'user_id' => 1,
                'description' => 'Test audit log description',
                'function' => 'test_audit_log_creation',                
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

        // Finally, update the last entered AuditLog to
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/audit_logs/' . $content['data'],
            [
                'user_id' => 2,
                'description' => 'Updated test audit log description',
                'function' => 'test_audit_log_update',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $content = $response->decodeResponseJson();
        
        $this->assertEquals($content['data']['user_id'], 2);
        $this->assertEquals($content['data']['description'], 'Updated test audit log description');
        $this->assertEquals($content['data']['function'], 'test_audit_log_update');
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
            'api/v1/audit_logs',
            [
                'user_id' => 1,
                'description' => 'Test audit log description',
                'function' => 'test_audit_log_for_deletion',
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

        // Finally, delete the last entered AuditLog to
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/audit_logs/' . $content['data'],
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