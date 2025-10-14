<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Application;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\ApplicationSeeder;
use Tests\Traits\MockExternalApis;
use App\Http\Traits\IntegrationOverride;
use Database\Seeders\EmailTemplateSeeder;


class ApplicationTest extends TestCase
{
    use IntegrationOverride;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/applications';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_get_all_applications_with_success(): void
    {
        $response = $this->json(
            'GET',
            self::TEST_URL,
            [],
            $this->header,
        );

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'app_id',
                    'client_id',
                    'image_link',
                    'description',
                    'team_id',
                    'user_id',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'permissions',
                    'team',
                    'user',
                    'notifications',
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
        $response->assertStatus(200);
    }

    public function test_get_application_by_id_with_success(): void
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Hello World',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
                'team_id' => 5,
                'user_id' => 2,
                'enabled' => true,
                'permissions' => [
                    1,
                    2,
                ],
                "notifications" => [
                    "t1@test.com",
                    "t2@test.com",
                    "t3@test.com"
                ],
            ],
            $this->header,
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

        $id = $contentCreate['data']['id'];

        // get by id
        $responseGet = $this->json(
            'GET',
            self::TEST_URL . '/' . $id,
            [],
            $this->header,
        );

        $responseGet->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'app_id',
                'client_id',
                'image_link',
                'description',
                'team_id',
                'user_id',
                'enabled',
                'created_at',
                'updated_at',
                'deleted_at',
                'permissions',
                'team',
                'user',
                'notifications',
            ],
        ]);
        $responseGet->assertStatus(200);
    }

    public function test_create_application_with_success()
    {
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Hello World',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
                'team_id' => 5,
                'user_id' => 2,
                'enabled' => true,
                'permissions' => [
                    1,
                    2,
                ],
                "notifications" => [
                    "t1@test.com",
                    "t2@test.com",
                    "t3@test.com"
                ],
            ],
            $this->header,
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
    }

    public function test_update_application_with_success()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Hello World',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
                'team_id' => 5,
                'user_id' => 2,
                'enabled' => true,
                'permissions' => [
                    1,
                    2,
                ],
                "notifications" => [
                    "t1@test.com",
                    "t2@test.com",
                    "t3@test.com"
                ],
            ],
            $this->header,
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

        $id = $contentCreate['data']['id'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'Hello World',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Update',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Update.',
                'team_id' => 2,
                'user_id' => 1,
                'enabled' => false,
                'permissions' => [
                    2,
                ],
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(200);
        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['name'], 'Hello World');
        $this->assertEquals($contentUpdate['data']['app_id'], $contentCreate['data']['app_id']);
        $this->assertEquals($contentUpdate['data']['client_id'], $contentCreate['data']['client_id']);
        $this->assertEquals($contentUpdate['data']['image_link'], 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Update');
        $this->assertEquals($contentUpdate['data']['team_id'], 2);
        $this->assertEquals($contentUpdate['data']['user_id'], 1);
        $this->assertEquals($contentUpdate['data']['enabled'], false);
    }

    public function test_edit_application_with_success()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Hello World',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
                'team_id' => 5,
                'user_id' => 2,
                'enabled' => true,
                'permissions' => [
                    1,
                    2,
                ],
                "notifications" => [
                    "t1@test.com",
                    "t2@test.com",
                    "t3@test.com"
                ],
            ],
            $this->header,
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

        $id = $contentCreate['data']['id'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'Hello World Update',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Update',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Update.',
                'team_id' => 2,
                'user_id' => 1,
                'enabled' => false,
                'permissions' => [
                    2,
                ],
                "notifications" => [
                    "t1@test.com"
                ],
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(200);
        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['name'], 'Hello World Update');
        $this->assertEquals($contentUpdate['data']['app_id'], $contentCreate['data']['app_id']);
        $this->assertEquals($contentUpdate['data']['client_id'], $contentCreate['data']['client_id']);
        $this->assertEquals($contentUpdate['data']['image_link'], 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Update');
        $this->assertEquals($contentUpdate['data']['team_id'], 2);
        $this->assertEquals($contentUpdate['data']['user_id'], 1);
        $this->assertEquals($contentUpdate['data']['enabled'], false);

        // edit
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'Hello World Edit',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Edit',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Edit.',
                'enabled' => true,
            ],
            $this->header,
        );

        $responseEdit->assertStatus(200);
        $contentEdit = $responseEdit->decodeResponseJson();
        $this->assertEquals($contentEdit['data']['name'], 'Hello World Edit');
        $this->assertEquals($contentEdit['data']['app_id'], $contentCreate['data']['app_id']);
        $this->assertEquals($contentEdit['data']['client_id'], $contentCreate['data']['client_id']);
        $this->assertEquals($contentEdit['data']['image_link'], 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Edit');
        $this->assertEquals($contentEdit['data']['enabled'], true);
    }

    public function test_delete_application_with_success()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Hello World',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
                'team_id' => 5,
                'user_id' => 2,
                'enabled' => true,
                'permissions' => [
                    1,
                    2,
                ],
                "notifications" => [
                    "t1@test.com",
                    "t2@test.com",
                    "t3@test.com"
                ],
            ],
            $this->header,
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

        $id = $contentCreate['data']['id'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'Hello World Update',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Update',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Update.',
                'team_id' => 2,
                'user_id' => 1,
                'enabled' => false,
                'permissions' => [
                    2,
                ],
                "notifications" => [
                    "t1@test.com",
                ],
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(200);
        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['name'], 'Hello World Update');
        $this->assertEquals($contentUpdate['data']['app_id'], $contentCreate['data']['app_id']);
        $this->assertEquals($contentUpdate['data']['client_id'], $contentCreate['data']['client_id']);
        $this->assertEquals($contentUpdate['data']['image_link'], 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Update');
        $this->assertEquals($contentUpdate['data']['team_id'], 2);
        $this->assertEquals($contentUpdate['data']['user_id'], 1);
        $this->assertEquals($contentUpdate['data']['enabled'], false);

        // edit
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'Hello World Edit',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Edit',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci. Edit.',
                'enabled' => true,
            ],
            $this->header,
        );

        $responseEdit->assertStatus(200);
        $contentEdit = $responseEdit->decodeResponseJson();
        $this->assertEquals($contentEdit['data']['name'], 'Hello World Edit');
        $this->assertEquals($contentEdit['data']['app_id'], $contentCreate['data']['app_id']);
        $this->assertEquals($contentEdit['data']['client_id'], $contentCreate['data']['client_id']);
        $this->assertEquals($contentEdit['data']['image_link'], 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam+Edit');
        $this->assertEquals($contentEdit['data']['enabled'], true);

        // delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $id,
            [],
            $this->header,
        );

        $responseDelete->assertStatus(200);
    }

    public function test_application_credentials_can_be_translated_to_teams_and_users()
    {
        $integration = Application::where('id', 1)->first();
        $teamId = null;
        $userId = null;

        $headers = [
            'x-application-id' => $integration->app_id,
            'x-client-id' => $integration->client_id,
        ];

        $this->overrideBothTeamAndUserId($teamId, $userId, $headers);

        $this->assertNotEquals($teamId, null);
        $this->assertNotEquals($userId, null);
    }

    public function test_application_credentials_can_create_dataset_defaults()
    {
        $integration = Application::where('id', 1)->first();
        $teamId = null;
        $userId = null;

        $headers = [
            'x-application-id' => $integration->app_id,
            'x-client-id' => $integration->client_id,
        ];

        $retVal = $this->injectApplicationDatasetDefaults($headers);

        $this->assertTrue(is_array($retVal));

        $this->assertEquals($retVal['user_id'], $integration->user_id);
        $this->assertEquals($retVal['team_id'], $integration->team_id);
        $this->assertEquals($retVal['create_origin'], 'API');
        $this->assertEquals($retVal['status'], 'ACTIVE');
    }

    public function test_application_update_clientid_by_application_id()
    {
        $initApp = Application::where('id', 1)->first();
        $initClientId = $initApp->client_id;

        $responseUpdateClientId = $this->json(
            'PATCH',
            self::TEST_URL . '/1/clientid',
            [],
            $this->header,
        );
        $responseUpdateClientId->assertStatus(200);

        $afterApp = Application::where('id', 1)->first();
        $afterClientId = $afterApp->client_id;

        $this->assertNotEquals($initClientId, $afterClientId);
    }
}
