<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\ApplicationSeeder;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/applications';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            ApplicationSeeder::class,
        ]);

        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
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

}
