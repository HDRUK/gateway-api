<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Library;
use Tests\Traits\Authorization;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\LibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    private $accessToken = '';
    const TEST_URL = '/api/v1/libraries';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            LibrarySeeder::class,
        ]);

        $response = $this->postJson('api/v1/auth', [
            'email' => 'developers@hdruk.ac.uk',
            'password' => 'Watch26Task?',
        ]);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $content = $response->decodeResponseJson();
        $this->accessToken = $content['access_token'];
    }

    /**
     * List all libraries.
     *
     * @return void
     */
    public function test_the_application_can_list_libraries()
    {
        $response = $this->json('GET', self::TEST_URL, [], [
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
                        'dataset',
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
     * Returns a single library
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_library()
    {
        $response = $this->json('POST', self::TEST_URL, [
            'dataset_id' => 2,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->json('GET', self::TEST_URL . '/' . $content['data'], [], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'user_id',
                    'dataset',
                ],
            ]);
    }

    /**
     * Creates a new library
     *
     * @return void
     */
    public function test_the_application_can_create_a_library()
    {
        $response = $this->json('POST', self::TEST_URL, [
            'dataset_id' => 1,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        // test filter has been linked correctly
        $newSearchId = $content['data'];

        $responseGet = $this->json('GET', self::TEST_URL . '/' . $newSearchId, [], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
    }

    /**
     * Tests that a library record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_library()
    {
        // create
        $response = $this->json('POST', self::TEST_URL, [
            'dataset_id' => 1,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        // update
        $response = $this->json('PUT', self::TEST_URL . '/' . $content['data'], [
            'dataset_id' => 2,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    /**
     * Tests that a library record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_library()
    {
        // create
        $responseCreate = $this->json('POST', self::TEST_URL, [
            'dataset_id' => 1,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals($contentCreate['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        $id = $contentCreate['data'];

        // edit (PUT)
        $responseUpdate = $this->json('PUT', self::TEST_URL . '/' . $id, [
            'dataset_id' => 2,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['dataset_id'], 2);

        // edit (PATCH)
        $responseEdit1 = $this->json('PATCH', self::TEST_URL . '/' . $id, [
            'dataset_id' => 1,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['dataset_id'], 1);
    }

    /**
     * Tests it can delete a library
     *
     * @return void
     */
    public function test_it_can_delete_a_library()
    {
        // create
        $response = $this->json('POST', self::TEST_URL, [
            'dataset_id' => 2,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        // delete
        $response = $this->json('DELETE', self::TEST_URL . '/' . $content['data'], [], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], Config::get('statuscodes.STATUS_OK.message'));
    }

    /**
     * Tests user permissions with a new library
     *
     * @return void
     */
    public function test_user_can_create_update_delete_a_library()
    {
        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $response = $this->json('POST', self::TEST_URL, [
            'dataset_id' => 1,
        ], $headerNonAdmin);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $content = $response->decodeResponseJson();
        $newSearchId = $content['data'];

        // test admin can view library
        $responseGet = $this->json('GET', self::TEST_URL . '/' . $newSearchId, [], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // test admin cannot edit or delete
        $responseUpdate = $this->json('PUT', self::TEST_URL . '/' . $newSearchId, [
            'dataset_id' => 2,
        ], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $responseUpdate->assertJsonStructure([
            'message',
        ]);
        $responseUpdate->assertStatus(500);

        $responseDelete = $this->json('DELETE', self::TEST_URL . '/' . $newSearchId, [], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $responseDelete->assertJsonStructure([
            'message',
        ]);
        $responseDelete->assertStatus(500);
    }
}
