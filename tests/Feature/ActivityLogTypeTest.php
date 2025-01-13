<?php

namespace Tests\Feature;

use Config;

use Tests\TestCase;
use Database\Seeders\ActivityLogTypeSeeder;

use Tests\Traits\MockExternalApis;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTypeTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = 'api/v1/activity_log_types';

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            ActivityLogTypeSeeder::class,
        ]);
    }

    /**
     * List all ActivityLogType
     *
     * @return void
     */
    public function test_the_application_can_list_activity_log_types()
    {
        $response = $this->get(self::TEST_URL, $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'name',
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
     * Tests that an ActivityLogType can be listed by id
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_activity_log_type()
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'test log type',
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get(self::TEST_URL . '/' . $content['data'], $this->header);

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
     * Tests that an ActivityLogType can be created
     *
     * @return void
     */
    public function test_the_application_can_create_an_activity_log_type()
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'test activity log type',
            ],
            $this->header
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
     * Tests it can update an ActivityLogType
     *
     * @return void
     */
    public function test_the_application_can_update_an_activity_log_types()
    {
        // Start by creating a new activity log type record for updating
        // within this test case
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'test activity log type',
            ],
            $this->header
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

        // Finally, update the last entered activity log type to
        // prove functionality
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $content['data'],
            [
                'name' => 'updated activity log type'
            ],
            $this->header
        );

        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['name'], 'updated activity log type');
    }

    /**
     * Tests it can edit an ActivityLogType
     *
     * @return void
     */
    public function test_id_can_edit_an_activity_log_type()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'test activity log type',
            ],
            $this->header
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'updated activity log type'
            ],
            $this->header
        );

        $contentUpdate = $responseUpdate->decodeResponseJson();

        $this->assertEquals($contentUpdate['data']['name'], 'updated activity log type');

        // edit
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [],
            $this->header
        );

        $contentEdit = $responseEdit->decodeResponseJson();

        $this->assertEquals($contentEdit['data']['name'], 'updated activity log type');

        // edit
        $responseEditSec = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'updated activity log type edit'
            ],
            $this->header
        );

        $contentEditSec = $responseEditSec->decodeResponseJson();

        $this->assertEquals($contentEditSec['data']['name'], 'updated activity log type edit');
    }

    /**
     * Tests it can delete an ActivityLogType
     *
     * @return void
     */
    public function test_it_can_delete_an_activity_log_type()
    {
        // Start by creating a new activity log type record for updating
        // within this test case
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'to be deleted',
            ],
            $this->header
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

        // Finally, delete the last entered activity log type to
        // prove functionality
        $response = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $content['data'],
            [],
            $this->header
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
