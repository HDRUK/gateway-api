<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\OrganisationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrganisationTest extends TestCase
{
    use RefreshDatabase;
    private $accessToken = '';    

    public function setUp() :void
    {
        parent::setUp();

        $this->seed([
            OrganisationSeeder::class,
            MinimalUserSeeder::class,
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
     * List all organisations.
     *
     * @return void
     */
    public function test_the_application_can_list_organisations()
    {
        $response = $this->get('api/v1/organisations', [
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
     * Returns a single organisation
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_organisation()
    {
        $response = $this->json(
            'POST',
            'api/v1/organisations',
            [
                'name' => 'Test Organisation',
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

        $response = $this->get('api/v1/organisations/' . $content['data'], [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'name',
                        'enabled',
                    ]
                ],
            ]);

    }


    /**
     * Creates a new organisation
     * 
     * @return void
     */
    public function test_the_application_can_create_a_organisation()
    {
        $response = $this->json(
            'POST',
            'api/v1/organisations',
            [
                'name' => 'Test Organisation',
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
     * Tests that a organisation record can be updated
     * 
     * @return void
     */
    public function test_the_application_can_update_a_organisation()
    {
        // Start by creating a new organisation record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/organisations',
            [
                'name' => 'Test Organisation',
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

        // Finally, update the last entered organisation to 
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/organisations/' . $content['data'],
            [
                'name' => 'Updated Test Organisation',
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
        $this->assertEquals($content['data']['name'], 'Updated Test Organisation');
        $this->assertEquals($content['data']['enabled'], true);
    }

    /**
     * Tests that a organisation record can be edited
     * 
     * @return void
     */
    public function test_the_application_can_edit_a_organisation()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/organisations',
            [
                'name' => 'Test Organisation',
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
            'api/v1/organisations/' . $id,
            [
                'name' => 'Updated Test Organisation',
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
        $this->assertEquals($contentUpdate['data']['name'], 'Updated Test Organisation');
        $this->assertEquals($contentUpdate['data']['enabled'], true);

        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/organisations/' . $id,
            [
                'name' => 'Edited Test Organisation',
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
        $this->assertEquals($contentEdit1['data']['name'], 'Edited Test Organisation');
    }

    /**
     * Tests it can delete a organisation
     * 
     * @return void
     */
    public function test_it_can_delete_a_organisation()
    {
        // Start by creating a new organisation record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/organisations',
            [
                'name' => 'Test Organisation',
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

        // Finally, update the last entered organisation to 
        // prove functionality        
        $response = $this->json(
            'DELETE',
            'api/v1/organisations/' . $content['data'],
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