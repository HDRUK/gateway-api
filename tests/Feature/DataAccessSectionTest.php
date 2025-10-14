<?php

namespace Tests\Feature;

// 
use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

use Illuminate\Support\Facades\DB;

class DataAccessSectionTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * List all dar sections.
     *
     * @return void
     */
    public function test_the_application_can_list_dar_sections()
    {
        $response = $this->get('api/v1/dar/sections', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'name',
                        'description',
                        'parent_section',
                        'order',
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

        $response = $this->get('api/v1/dar/sections?per_page=-1', $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

    }

    /**
     * Returns a single dar application
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_dar_application()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/sections',
            [
                'name' => 'A section',
                'description' => 'A test section',
                'order' => 1,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/dar/sections/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'name',
                    'description',
                    'parent_section',
                    'order',
                ],
            ]);
    }

    /**
     * Fails to return a single dar section
     *
     * @return void
     */
    public function test_the_application_fails_to_list_a_single_dar_section()
    {
        $beyondId = DB::table('dar_sections')->max('id') + 1;
        $response = $this->get('api/v1/dar/sections/' . $beyondId, $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    /**
     * Creates a new dar section
     *
     * @return void
     */
    public function test_the_application_can_create_a_dar_section()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/sections',
            [
                'name' => 'A section',
                'description' => 'A test section',
                'order' => 1,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $content = $response->decodeResponseJson();
        $response = $this->get('api/v1/dar/sections/' . $content['data'], $this->header);
        // Test parent section defaulted to null
        $this->assertNull($response['data']['parent_section']);
    }

    /**
     * Fails to create a new section
     *
     * @return void
     */
    public function test_the_application_fails_to_create_a_dar_section()
    {
        // Attempt to create section with incomplete information
        $response = $this->json(
            'POST',
            'api/v1/dar/sections',
            [
                'name' => 'A new section',
                'order' => 1
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJsonStructure([
                'status',
                'message',
                'errors',
            ]);
    }

    /**
     * Tests that a dar section record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_dar_section()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/sections',
            [
                'name' => 'A section',
                'description' => 'A test section',
                'order' => 1,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'PUT',
            'api/v1/dar/sections/' . $content['data'],
            [
                'name' => 'A section',
                'description' => 'Updated',
                'order' => 1,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals('Updated', $content['data']['description']);
        $this->assertEquals('A section', $content['data']['name']);
    }

    /**
     * Tests that a dar section record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_dar_section()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/sections',
            [
                'name' => 'A section',
                'description' => 'A test section',
                'order' => 1,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $sectionId = $content['data'];

        $response = $this->json(
            'PATCH',
            'api/v1/dar/sections/' . $sectionId,
            [
                'description' => 'Edited',
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals('Edited', $content['data']['description']);
        $this->assertEquals('A section', $content['data']['name']);
    }

    /**
     * Tests it can delete a dar section
     *
     * @return void
     */
    public function test_it_can_delete_a_dar_section()
    {

        $response = $this->json(
            'POST',
            'api/v1/dar/sections',
            [
                'name' => 'A section',
                'description' => 'A test section',
                'order' => 1,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'DELETE',
            'api/v1/dar/sections/' . $content['data'],
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }
}
