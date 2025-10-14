<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\ActivityLogUserTypeSeeder;
use Tests\Traits\MockExternalApis;


class ActivityLogUserTypeTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * List all ActivityLogUserType
     *
     * @return void
     */
    public function test_the_application_can_list_activity_log_user_types()
    {
        $response = $this->get('api/v1/activity_log_user_types', $this->header);

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
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/activity_log_user_types/' . $content['data'], $this->header);

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

        // Finally, update the last entered activity log user type to
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/activity_log_user_types/' . $content['data'],
            [
                'name' => 'updated activity log user type'
            ],
            $this->header
        );

        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['name'], 'updated activity log user type');
    }

    /**
     * Tests it can edit an ActivityLogUserType
     *
     * @return void
     */
    public function test_the_application_can_edit_an_activity_log_user_types()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/activity_log_user_types',
            [
                'name' => 'test activity log user type',
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
            'api/v1/activity_log_user_types/' . $id,
            [
                'name' => 'updated activity log user type'
            ],
            $this->header
        );

        $contentUpdate = $responseUpdate->decodeResponseJson();

        $this->assertEquals($contentUpdate['data']['name'], 'updated activity log user type');

        // edit
        $responseUpdateOne = $this->json(
            'PATCH',
            'api/v1/activity_log_user_types/' . $id,
            [

            ],
            $this->header
        );

        $contentUpdateOne = $responseUpdateOne->decodeResponseJson();

        $this->assertEquals($contentUpdateOne['data']['name'], 'updated activity log user type');

        // edit
        $responseUpdateSec = $this->json(
            'PATCH',
            'api/v1/activity_log_user_types/' . $id,
            [
                'name' => 'updated activity log user type edit'
            ],
            $this->header
        );

        $contentUpdateSec = $responseUpdateSec->decodeResponseJson();

        $this->assertEquals($contentUpdateSec['data']['name'], 'updated activity log user type edit');
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

        // Finally, delete the last entered activity log user type to
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/activity_log_user_types/' . $content['data'],
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
