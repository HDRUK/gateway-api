<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    public const TEST_URL = '/api/v1/audit_logs';

    protected $header = [];

    /**
     * List all AuditLog's
     *
     * @return void
     */
    public function test_list_all_audit_logs()
    {
        $response = $this->json(
            'GET',
            self::TEST_URL,
            [],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'user_id',
                        'team_id',
                        'action_type',
                        'action_name',
                        'description',
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
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'user_id' => 1,
                'team_id' => 2,
                'action_type' => 'CREATE',
                'action_name' => 'Gateway API',
                'description' => 'Test audit log description',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();

        $responseGet = $this->json(
            'GET',
            self::TEST_URL . '/' . $contentCreate['data'],
            [],
            $this->header,
        );

        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'user_id',
                    'team_id',
                    'action_type',
                    'action_name',
                    'description',
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
            self::TEST_URL,
            [
                'user_id' => 1,
                'team_id' => 2,
                'action_type' => 'CREATE',
                'action_name' => 'Gateway API',
                'description' => 'Test audit log description',
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
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
            self::TEST_URL,
            [
                'user_id' => 1,
                'team_id' => 2,
                'action_type' => 'CREATE',
                'action_name' => 'Gateway API',
                'description' => 'Test audit log description',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals($contentCreate['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        $id = $contentCreate['data'];

        // edit
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'action_type' => 'UPDATE',
                'action_name' => 'Translation Service',
                'description' => 'Test audit log description edit',
            ],
            $this->header,
        );
        $contentEdit = $responseEdit->decodeResponseJson();
        $this->assertEquals($contentEdit['data']['id'], $id);
        $this->assertEquals($contentEdit['data']['action_type'], 'UPDATE');
        $this->assertEquals($contentEdit['data']['action_name'], 'Translation Service');
        $this->assertEquals($contentEdit['data']['description'], 'Test audit log description edit');
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
            self::TEST_URL,
            [
                'user_id' => 1,
                'team_id' => 2,
                'action_type' => 'CREATE',
                'action_name' => 'Gateway API',
                'description' => 'Test audit log description',
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, update the last entered AuditLog to
        // prove functionality
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $content['data'],
            [
                'user_id' => 2,
                'team_id' => 2,
                'action_type' => 'CREATE',
                'action_name' => 'Gateway API',
                'description' => 'Updated test audit log description',
            ],
            $this->header,
        );

        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['user_id'], 2);
        $this->assertEquals($content['data']['description'], 'Updated test audit log description');
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
            self::TEST_URL,
            [
                'user_id' => 1,
                'team_id' => 2,
                'action_type' => 'CREATE',
                'action_name' => 'Gateway API',
                'description' => 'Test audit log description',
            ],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, delete the last entered AuditLog to
        // prove functionality
        $response = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $content['data'],
            [],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_OK.message')
        );
    }
}
