<?php

namespace Tests\Feature;

// use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\FilterSeeder;

class FilterTest extends TestCase
{
    use FastRefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private $accessToken = '';

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed(
            [
            MinimalUserSeeder::class,
            FilterSeeder::class,
            ]
        );
    }

    /**
     * List all filters.
     *
     * @return void
     */
    public function test_the_application_can_list_filters()
    {
        $response = $this->get('api/v1/filters', [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'type',
                        'keys',
                        'buckets',
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
     * Returns a single filter
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_filter()
    {
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'keys' => 'purpose',
                'enabled' => 0,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/filters/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'type',
                    'keys',
                    'buckets',
                    'enabled',
                ],
            ]);
    }

    /**
     * Creates a new filter
     *
     * @return void
     */
    public function test_the_application_can_create_a_filter()
    {
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'keys' => 'purpose',
                'enabled' => 0,
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
     * Tests that a filter record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_filter()
    {
        // Start by creating a new filter record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'keys' => 'purposeOld',
                'enabled' => 0,
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

        // Finally, update the last entered filter to
        // prove functionality
        $response = $this->json(
            'PUT',
            'api/v1/filters/' . $content['data'],
            [
                'type' => 'project',
                'keys' => 'purposeNew',
                'enabled' => 1,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['keys'], 'purposeNew');
        $this->assertEquals($content['data']['enabled'], 1);
    }


    /**
     * Tests that a filter record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_filter()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'keys' => 'purposeOld',
                'enabled' => 0,
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

        // update
        $responseUpdate = $this->json(
            'PUT',
            'api/v1/filters/' . $id,
            [
                'type' => 'project',
                'keys' => 'purposeNew',
                'enabled' => 1,
            ],
            $this->header
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['keys'], 'purposeNew');
        $this->assertEquals($contentUpdate['data']['enabled'], 1);

        // edit
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/filters/' . $id,
            [
                'keys' => 'purposeNewNew',
            ],
            $this->header
        );

        $responseEdit1->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit1 = $responseEdit1->decodeResponseJson();
        $this->assertEquals($contentEdit1['data']['keys'], 'purposeNewNew');

        // edit
        $responseEdit2 = $this->json(
            'PATCH',
            'api/v1/filters/' . $id,
            [
                'keys' => 'purposeNewNew',
                'enabled' => 0,
            ],
            $this->header
        );

        $responseEdit2->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit2 = $responseEdit2->decodeResponseJson();
        $this->assertEquals($contentEdit2['data']['keys'], 'purposeNewNew');
        $this->assertEquals($contentEdit2['data']['enabled'], 0);
    }

    /**
     * Tests it can delete a filter
     *
     * @return void
     */
    public function test_it_can_delete_a_filter()
    {
        // Start by creating a new filter record for updating
        // within this test case
        $response = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'keys' => 'purpose',
                'enabled' => 0,
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

        // Finally, update the last entered filter to
        // prove functionality
        $response = $this->json(
            'DELETE',
            'api/v1/filters/' . $content['data'],
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
