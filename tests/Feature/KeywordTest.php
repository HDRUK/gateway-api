<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class KeywordTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/keywords';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * List all keywords.
     *
     * @return void
     */
    public function test_the_application_can_list_keywords()
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

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
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * Returns a single keyword
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_keyword()
    {
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Test Keyword',
                'enabled' => false,
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $responseCreate->decodeResponseJson();

        $responseGet = $this->json(
            'GET',
            self::TEST_URL . '/' . $content['data'],
            [],
            $this->header,
        );

        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'enabled',
                    'created_at',
                    'updated_at',
                ]
            ],
        ]);
    }

    /**
     * Creates a new keyword
     *
     * @return void
     */
    public function test_the_application_can_create_a_keyword()
    {
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Test Keyword',
                'enabled' => false,
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $content = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );
    }

    /**
     * Tests that a keyword record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_keyword()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Test Keyword',
                'enabled' => false,
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);
        $contentCreate = $responseCreate->decodeResponseJson();

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $contentCreate['data'],
            [
                'name' => 'Test Keyword Update',
                'enabled' => false,
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);
        $contentUpdate = $responseUpdate->decodeResponseJson();

        $this->assertEquals(
            $contentUpdate['data']['name'],
            'Test Keyword Update'
        );
    }

    /**
     * Tests that a keyword record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_keyword()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Test Keyword',
                'enabled' => false,
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);
        $contentCreate = $responseCreate->decodeResponseJson();

        // update
        $responseUpdate = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $contentCreate['data'],
            [
                'name' => 'Test Keyword Update',
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);
        $contentUpdate = $responseUpdate->decodeResponseJson();

        $this->assertEquals(
            $contentUpdate['data']['name'],
            'Test Keyword Update'
        );
    }

    /**
     * Tests it can delete a keyword
     *
     * @return void
     */
    public function test_it_can_delete_a_keyword(): void
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'Test Keyword',
                'enabled' => false,
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);
        $contentCreate = $responseCreate->decodeResponseJson();

        // delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $contentCreate['data'],
            [],
            $this->header,
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
        $contentDelete = $responseDelete->decodeResponseJson();

        $this->assertEquals(
            $contentDelete['message'],
            Config::get('statuscodes.STATUS_OK.message')
        );
    }
}
