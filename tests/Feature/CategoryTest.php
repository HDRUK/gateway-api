<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;
    private $accessToken = '';    

    public function setUp() :void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            CategorySeeder::class,
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
     * List all categories.
     *
     * @return void
     */
    public function test_the_application_can_list_categories()
    {
        $response = $this->get('api/v1/categories', [
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
                        'name',
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
     * Returns a single category
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_category()
    {
        $response = $this->json(
            'POST',
            'api/v1/categories',
            [
                'name' => 'Test Category',
                'enabled' => false,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/categories/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'name',
                    'enabled',
                ],
            ]);

    }

    /**
     * Creates a new category
     * 
     * @return void
     */
    public function test_the_application_can_create_a_category()
    {
        $response = $this->json(
            'POST',
            'api/v1/categories',
            [
                'name' => 'Test Category',
                'enabled' => false,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );
    }

    /**
     * Tests that a category record can be updated
     * 
     * @return void
     */
    public function test_the_application_can_update_a_category()
    {
        // Start by creating a new category record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/categories',
            [
                'name' => 'Test Category',
                'enabled' => false,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, update the last entered category to 
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/categories/' . $content['data'],
            [
                'name' => 'Updated Test Category',
                'enabled' => true,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['name'], 'Updated Test Category');
        $this->assertEquals($content['data']['enabled'], true);
    }

    /**
     * Tests that a category record can be edited
     * 
     * @return void
     */
    public function test_the_application_can_edit_a_category()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/categories',
            [
                'name' => 'Test Category',
                'enabled' => false,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals($contentCreate['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            'api/v1/categories/' . $id,
            [
                'name' => 'Updated Test Category',
                'enabled' => true,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['name'], 'Updated Test Category');
        $this->assertEquals($contentUpdate['data']['enabled'], true);

        // edit
        $responseEdit1 = $this->json(
            'Patch',
            'api/v1/categories/' . $id,
            [
                'name' => 'Updated Test Category - e1',
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['name'], 'Updated Test Category - e1');

        // edit
        $responseEdit2 = $this->json(
            'Patch',
            'api/v1/categories/' . $id,
            [
                'name' => 'Updated Test Category - e2',
                'enabled' => false,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseEdit2->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();
        $this->assertEquals($contentEdit2['data']['name'], 'Updated Test Category - e2');
        $this->assertEquals($contentEdit2['data']['enabled'], false);
    }

    /**
     * Tests it can delete a category
     * 
     * @return void
     */
    public function test_it_can_delete_a_category()
    {
        // Start by creating a new category record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/categories',
            [
                'name' => 'Test Category',
                'enabled' => false,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, update the last entered category to 
        // prove functionality        
        $response = $this->json(
            'DELETE',
            'api/v1/categories/' . $content['data'],
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'],
            Config::get('statuscodes.STATUS_OK.message')
        );
    }
}
