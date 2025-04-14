<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\NotificationSeeder;
use Tests\Traits\MockExternalApis;

class NotificationTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            NotificationSeeder::class,
        ]);
    }

    /**
     * List all notifications.
     *
     * @return void
     */
    public function test_the_application_can_list_notifications()
    {
        $response = $this->get('api/v1/notifications', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'notification_type',
                        'message',
                        'opt_in',
                        'enabled',
                        'email',
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
     * Returns a single notification
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_notification()
    {
        $response = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/notifications/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'notification_type',
                    'message',
                    'opt_in',
                    'enabled',
                    'email',
                    'user_id',
                ],
            ]);

    }

    /**
     * Creates a new notification
     *
     * @return void
     */
    public function test_the_application_can_create_a_notification()
    {
        $response = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
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

        $response = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => 'test@test.com',
                'user_id' => null,
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
     * Creates a new notification but fails due to missing email and user_id
     *
     * @return void
     */
    public function test_the_application_cannot_create_a_notification_without_email_or_user_id()
    {
        $response = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => null,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $this->assertEquals($content['message'], 'Invalid argument(s)');
    }

    /**
     * Update an existing notification
     *
     * @return void
     */
    public function test_the_application_can_update_a_notification()
    {
        // Start by creating a new notification record for updating
        // within this test
        $response = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 0,
                'email' => null,
                'user_id' => 3,
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

        // Finally, update the last entered notification to
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/notifications/' . $content['data'],
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'New message',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['message'], 'New message');
        $this->assertEquals($content['data']['enabled'], 1);
    }

    /**
     * Edit an existing notification
     *
     * @return void
     */
    public function test_the_application_can_edit_a_notification()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 0,
                'email' => null,
                'user_id' => 3,
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
            'api/v1/notifications/' . $id,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'New message',
                'opt_in' => 1,
                'enabled' => 1,
                'email' => null,
                'user_id' => 3,
            ],
            $this->header
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['message'], 'New message');
        $this->assertEquals($contentUpdate['data']['enabled'], 1);

        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/notifications/' . $id,
            [
                'message' => 'New message e1',
            ],
            $this->header
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['message'], 'New message e1');


        // edit
        $responseEdit2 = $this->json(
            'PATCH',
            'api/v1/notifications/' . $id,
            [
                'message' => 'New message e2',
                'opt_in' => 0,
                'enabled' => 0,
            ],
            $this->header
        );

        $responseEdit2->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();
        $this->assertEquals($contentEdit2['data']['message'], 'New message e2');
        $this->assertEquals($contentEdit2['data']['opt_in'], 0);
        $this->assertEquals($contentEdit2['data']['enabled'], 0);
    }
    /**
     * Tests it can delete a notification
     *
     * @return void
     */
    public function test_it_can_delete_a_notification()
    {
        // Start by creating a new notification record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'opt_in' => 1,
                'enabled' => 0,
                'email' => null,
                'user_id' => 3,
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

        // Finally, delete the notification we just created
        $response = $this->json(
            'DELETE',
            'api/v1/notifications/' . $content['data'],
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
