<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TypeCategorySeeder;
use Tests\Traits\MockExternalApis;

class TypeCategoryTest extends TestCase
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
            TypeCategorySeeder::class,
        ]);
    }

    /**
     * List all type categories.
     *
     * @return void
     */
    public function test_the_application_can_list_type_categories()
    {
        $response = $this->get('api/v1/type_categories', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'name',
                        'description',
                        'enabled',
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
     * Returns a single type category
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_type_category()
    {
        $response = $this->json(
            'POST',
            'api/v1/type_categories',
            [
                'name' => 'Test Type Category',
                'description' => 'A Type Category',
                'enabled' => false,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/type_categories/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'name',
                    'description',
                    'enabled',
                ],
            ]);

    }

    /**
     * Creates a new type category
     *
     * @return void
     */
    public function test_the_application_can_create_a_type_category()
    {
        $response = $this->json(
            'POST',
            'api/v1/type_categories',
            [
                'name' => 'Test Type Category',
                'description' => 'A Type Category',
                'enabled' => false,
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
     * Tests that a type category record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_type_category()
    {
        // Start by creating a new type category record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/type_categories',
            [
                'name' => 'Test Type Category',
                'description' => 'A Type Category',
                'enabled' => false,
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

        // Finally, update the last entered type category to
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/type_categories/' . $content['data'],
            [
                'name' => 'Updated Test Type Category',
                'description' => 'A Type Category',
                'enabled' => true,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['name'], 'Updated Test Type Category');
        $this->assertEquals($content['data']['enabled'], true);
    }

    /**
     * Tests that a type category record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_type_category()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/type_categories',
            [
                'name' => 'Test Type Category',
                'description' => 'A Type Category',
                'enabled' => false,
            ],
            $this->header
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
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/type_categories/' . $id,
            [
                'name' => 'Edited Test Type Category',
            ],
            $this->header
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['name'], 'Edited Test Type Category');
    }

    /**
     * Tests it can delete a type category
     *
     * @return void
     */
    public function test_it_can_delete_a_type_category()
    {
        // Start by creating a new type category record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/type_categories',
            [
                'name' => 'Test Type Category',
                'description' => 'A Type Category',
                'enabled' => false,
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

        // Finally, update the last entered type category to
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/type_categories/' . $content['data'],
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
