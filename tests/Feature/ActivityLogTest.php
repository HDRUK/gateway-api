<?php

namespace Tests\Feature;

use Config;

use Tests\TestCase;
use App\Models\User;
use App\Models\ActivityLogType;
use App\Models\ActivityLogUserType;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\ActivityLogTypeSeeder;
use Database\Seeders\ActivityLogUserTypeSeeder;
use Database\Seeders\ActivityLogSeeder;

use Tests\Traits\MockExternalApis;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/activity_logs';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            ActivityLogTypeSeeder::class,
            ActivityLogUserTypeSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }

    /**
     * List all ActivityLogs
     *
     * @return void
     */
    public function test_the_application_can_list_activity_logs()
    {
        $response = $this->get(self::TEST_URL, $this->header);

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
                'current_page',
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
     * Tests that an activity log can be listed by id
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_activity_log()
    {
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'event_type' => 'test_case',
                'user_type_id' => ActivityLogUserType::all()->random()->id,
                'log_type_id' => ActivityLogType::all()->random()->id,
                'user_id' => User::all()->random()->id,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
            ],
            $this->header,
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
            self::TEST_URL,
            [
                'event_type' => 'test_case',
                'user_type_id' => ActivityLogUserType::all()->random()->id,
                'log_type_id' => ActivityLogType::all()->random()->id,
                'user_id' => User::all()->random()->id,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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
            self::TEST_URL,
            [
                'event_type' => 'test_case',
                'user_type_id' => ActivityLogUserType::all()->random()->id,
                'log_type_id' => ActivityLogType::all()->random()->id,
                'user_id' => User::all()->random()->id,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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

        // Finally, update the last entered activity log to
        // prove functionality
        $user_type_id = ActivityLogUserType::all()->random()->id;
        $log_type_id = ActivityLogType::all()->random()->id;
        $user_id = User::all()->random()->id;
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $content['data'],
            [
                'event_type' => 'updated_test_case',
                'user_type_id' => $user_type_id,
                'log_type_id' => $log_type_id,
                'user_id' => $user_id,
                'version' => '1.0.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
            ],
            $this->header,
        );

        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['event_type'], 'updated_test_case');
        $this->assertEquals($content['data']['user_type_id'], $user_type_id);
        $this->assertEquals($content['data']['log_type_id'], $log_type_id);
        $this->assertEquals($content['data']['user_id'], $user_id);
        $this->assertEquals($content['data']['version'], '1.0.0');
    }

    /**
     * Tests it can edit an activity log
     *
     * @return void
     */
    public function test_it_can_edit_an_activity_log()
    {
        // Start by creating a new activity log record for updating
        $response = $this->json(
            'POST',
            self::TEST_URL,
            [
                'event_type' => 'test_case',
                'user_type_id' => ActivityLogUserType::all()->random()->id,
                'log_type_id' => ActivityLogType::all()->random()->id,
                'user_id' => User::all()->random()->id,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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

        // id
        $id = $content['data'];

        // update
        $user_type_id = ActivityLogUserType::all()->random()->id;
        $log_type_id = ActivityLogType::all()->random()->id;
        $user_id = User::all()->random()->id;
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'event_type' => 'updated_test_case',
                'user_type_id' => $user_type_id,
                'log_type_id' => $log_type_id,
                'user_id' => $user_id,
                'version' => '1.0.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
            ],
            $this->header,
        );

        $content = $response->decodeResponseJson();

        $this->assertEquals($content['data']['event_type'], 'updated_test_case');
        $this->assertEquals($content['data']['user_type_id'], $user_type_id);
        $this->assertEquals($content['data']['log_type_id'], $log_type_id);
        $this->assertEquals($content['data']['user_id'], $user_id);
        $this->assertEquals($content['data']['version'], '1.0.0');

        // edit/patch
        $responsePatch1 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'event_type' => 'updated_test_case_edit',
            ],
            $this->header,
        );

        $contentPatch1 = $responsePatch1->decodeResponseJson();

        $this->assertEquals($contentPatch1['data']['event_type'], 'updated_test_case_edit');

        // edit/patch
        $responsePatch2 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'event_type' => 'updated_test_case_edit_another',
                'user_type_id' => 1,
            ],
            $this->header,
        );

        $contentPatch2 = $responsePatch2->decodeResponseJson();

        $this->assertEquals($contentPatch2['data']['event_type'], 'updated_test_case_edit_another');
        $this->assertEquals($contentPatch2['data']['user_type_id'], 1);

        // edit/patch
        $user_type_id = ActivityLogUserType::all()->random()->id;
        $log_type_id = ActivityLogType::all()->random()->id;
        $user_id = User::all()->random()->id;
        $responsePatch3 = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'event_type' => 'updated_test_case',
                'user_type_id' => $user_type_id,
                'log_type_id' => $log_type_id,
                'user_id' => $user_id,
                'version' => '1.0.1',
                'html' => '<b>something else</b>',
                'plain_text' => 'something else',
                'user_id_mongo' => 'user_id_mongo-new',
                'version_id_mongo' => 'version_id_mongo-new',
            ],
            $this->header,
        );

        $contentPatch3 = $responsePatch3->decodeResponseJson();

        $this->assertEquals($contentPatch3['data']['event_type'], 'updated_test_case');
        $this->assertEquals($contentPatch3['data']['user_type_id'], $user_type_id);
        $this->assertEquals($contentPatch3['data']['log_type_id'], $log_type_id);
        $this->assertEquals($contentPatch3['data']['user_id'], $user_id);
        $this->assertEquals($contentPatch3['data']['version'], '1.0.1');
        $this->assertEquals($contentPatch3['data']['user_id_mongo'], 'user_id_mongo-new');
        $this->assertEquals($contentPatch3['data']['version_id_mongo'], 'version_id_mongo-new');
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
                'event_type' => 'test_case',
                'user_type_id' => ActivityLogUserType::all()->random()->id,
                'log_type_id' => ActivityLogType::all()->random()->id,
                'user_id' => User::all()->random()->id,
                'version' => '2.1.0',
                'html' => '<b>something</b>',
                'plain_text' => 'something',
                'user_id_mongo' => 'blah-blah-blah',
                'version_id_mongo' => 'blah-blah-blah-2',
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

        // Finally, delete the last entered activity log to
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
