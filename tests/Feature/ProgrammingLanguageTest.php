<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\ProgrammingLanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProgrammingLanguageTest extends TestCase
{
    use RefreshDatabase;
    private $accessToken = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            ProgrammingLanguageSeeder::class,
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
     * List all programming languages.
     *
     * @return void
     */
    public function test_the_application_can_list_programming_languages()
    {
        $response = $this->get('api/v1/programming_languages', [
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
     * Returns a single programming language
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_programming_language()
    {
        $response = $this->json(
            'POST',
            'api/v1/programming_languages',
            [
                'name' => 'Test Programming Language',
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

        $response = $this->get('api/v1/programming_languages/' . $content['data'], [
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
     * Creates a new programming language
     *
     * @return void
     */
    public function test_the_application_can_create_a_programming_language()
    {
        $response = $this->json(
            'POST',
            'api/v1/programming_languages',
            [
                'name' => 'Test Programming Language',
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
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );
    }

    /**
     * Tests that a programming language record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_programming_language()
    {
        // Start by creating a new programming language record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/programming_languages',
            [
                'name' => 'Test Programming Language',
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
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, update the last entered programming language to
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/programming_languages/' . $content['data'],
            [
                'name' => 'Updated Test Programming Language',
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
        $this->assertEquals($content['data']['name'], 'Updated Test Programming Language');
        $this->assertEquals($content['data']['enabled'], true);
    }

    /**
     * Tests that a programming language record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_programming_language()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/programming_languages',
            [
                'name' => 'Test Programming Language',
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

        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/programming_languages/' . $id,
            [
                'name' => 'Edited Test Programming Language',
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
        $this->assertEquals($contentEdit1['data']['name'], 'Edited Test Programming Language');
    }

    /**
     * Tests it can delete a programming language
     *
     * @return void
     */
    public function test_it_can_delete_a_programming_language()
    {
        // Start by creating a new programming language record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/programming_languages',
            [
                'name' => 'Test Programming Language',
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
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Finally, update the last entered programming language to
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/programming_languages/' . $content['data'],
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
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_OK.message')
        );
    }
}
