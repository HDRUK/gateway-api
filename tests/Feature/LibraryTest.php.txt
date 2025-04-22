<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Library;
use App\Models\User;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\MinimalUserSeeder;
use Tests\Traits\MockExternalApis;

class LibraryTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/libraries';

    protected $header = [];
    protected $user = null;


    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
        ]);

        $this->user = User::where('id', 1)->first();

        Library::factory(10)->create(['user_id' => $this->user->id]);
        Library::factory(10)->create(['user_id' => User::all()->random()]);
    }

    /**
     * List all libraries.
     *
     * @return void
     */
    public function test_the_application_can_list_libraries()
    {

        $response = $this->json('GET', self::TEST_URL, [], $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [ // Assuming 'data' is an array of libraries
                    'id',
                    'created_at',
                    'updated_at',
                    'user_id',
                    'dataset_id',
                    'dataset_name',
                    'dataset_status',
                    'data_provider_id',
                    'data_provider_dar_status',
                    'data_provider_name',
                    'data_provider_dar_enabled',
                    'data_provider_member_of',
                    'dataset_is_cohort_discovery',
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

        $userLibrary = Library::where('user_id', $this->user->id)->pluck('id')->toArray();
        $ids = array_map(function ($dataset) {
            return $dataset['id'];
        }, $response['data']);

        $differenceArray = array_diff($userLibrary, $ids);
        $this->assertEmpty($differenceArray);
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
        ], $this->header);


        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->json('GET', self::TEST_URL . '/' . $content['data'], [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'user_id',
                    'dataset_id',
                    'dataset_status',
                    'data_provider_id',
                    'data_provider_dar_status',
                    'data_provider_name',
                    'data_provider_name',
                    'data_provider_dar_enabled',
                    'data_provider_member_of',
                    'dataset_is_cohort_discovery',
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
        ], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        // test filter has been linked correctly
        $newSearchId = $content['data'];

        $responseGet = $this->json('GET', self::TEST_URL . '/' . $newSearchId, [], $this->header);
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
        ], $this->header);

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
        ], $this->header);

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
        ], $this->header);

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
        ], $this->header);

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
        ], $this->header);

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
        ], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        // delete
        $response = $this->json('DELETE', self::TEST_URL . '/' . $content['data'], [], $this->header);

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
        $responseGet = $this->json('GET', self::TEST_URL . '/' . $newSearchId, [], $this->header);
        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // test admin cannot edit or delete
        $responseUpdate = $this->json('PUT', self::TEST_URL . '/' . $newSearchId, [
            'dataset_id' => 2,
        ], $this->header);

        $responseUpdate->assertJsonStructure([
            'message',
        ]);
        $responseUpdate->assertStatus(500);

        $responseDelete = $this->json('DELETE', self::TEST_URL . '/' . $newSearchId, [], $this->header);

        $responseDelete->assertJsonStructure([
            'message',
        ]);
        $responseDelete->assertStatus(500);
    }
}
