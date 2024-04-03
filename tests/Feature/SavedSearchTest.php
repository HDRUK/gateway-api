<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Database\Seeders\FilterSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SavedSearchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    
    private $accessToken = '';    

    public function setUp() :void
    {
        parent::setUp();

        $this->seed([
            FilterSeeder::class,
            MinimalUserSeeder::class,
            SavedSearchSeeder::class,
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
     * List all saved_searches.
     *
     * @return void
     */
    public function test_the_application_can_list_saved_searches()
    {
        $response = $this->get('api/v1/saved_searches', [
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
                        'search_term',
                        'search_endpoint',
                        'enabled',
                        'filters',
                        'user_id',
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

        $content = $response->decodeResponseJson();
        $numResults = count($content['data']);

        // filter by name
        $response = $this->get('api/v1/saved_searches?name=in', [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $contentFilter = $response->decodeResponseJson();
        $numResultsFilter = count($contentFilter['data']);

        $this->assertTrue($numResultsFilter < $numResults);
    }

    /**
     * Returns a single saved search
     * 
     * @return void
     */
    public function test_the_application_can_list_a_single_saved_search()
    {
        $response = $this->json(
            'POST',
            'api/v1/saved_searches',
            [
                'name' => 'Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => false,
                'filters' => [
                    0 => [
                        'id' => 1,
                        'terms' => [
                            'term a',
                            'term b',
                        ]
                    ]
                ],
                'user_id',
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

        $response = $this->get('api/v1/saved_searches/' . $content['data'], [
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
                        'search_term',
                        'search_endpoint',
                        'enabled',
                        'filters',
                        'user_id',
                    ]
                ],
            ]);

    }

    /**
     * Creates a new saved search
     * 
     * @return void
     */
    public function test_the_application_can_create_a_saved_search()
    {
        // Create a filter for the test
        $responseFilter = $this->json(
            'POST',
            'api/v1/filters',
            [
                'type' => 'project',
                'value' => 'Some value here',
                'keys' => 'purpose',
                'enabled' => 0,
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );
        $contentFilter = $responseFilter->decodeResponseJson();
        $filterId = $contentFilter['data'];

        $response = $this->json(
            'POST',
            'api/v1/saved_searches',
            [
                'name' => 'Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => false,
                'filters' => [
                    0 => [
                        'id' => $filterId,
                        'terms' => [
                            'term a',
                            'term b',
                        ]
                    ]
                ],
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

        // test filter has been linked correctly
        $newSearchId = $content['data'];

        $responseGet = $this->get('api/v1/saved_searches/' . $newSearchId, [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);

        $content = $responseGet->decodeResponseJson();
        $this->assertArrayHasKey('filters', $content['data'][0]);
        $this->assertEquals($content['data'][0]['filters'][0]['id'], $filterId);
    }

    /**
     * Tests that a saved search record can be updated
     * 
     * @return void
     */
    public function test_the_application_can_update_a_saved_search()
    {
        // create
        $response = $this->json(
            'POST',
            'api/v1/saved_searches',
            [
                'name' => 'Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => false,
                'filters' => [
                    0 => [
                        'id' => 1,
                        'terms' => [
                            'term a',
                            'term b',
                        ]
                    ]
                ],
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

        // update
        $response = $this->json(
            'PUT',
            'api/v1/saved_searches/' . $content['data'],
            [
                'name' => 'Updated Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => true,
                'filters' => [
                    0 => [
                        'id' => 1,
                        'terms' => [
                            'term a',
                            'term b',
                        ]
                    ]
                ],
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
        $this->assertEquals($content['data']['name'], 'Updated Test Saved Search');
        $this->assertEquals($content['data']['enabled'], true);
    }

    /**
     * Tests that a saved search record can be edited
     * 
     * @return void
     */
    public function test_the_application_can_edit_a_saved_search()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            'api/v1/saved_searches',
            [
                'name' => 'Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => false,
                'filters' => [
                    0 => [
                        'id' => 1,
                        'terms' => [
                            'term a',
                            'term b',
                        ]
                    ]
                ],
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

        // edit (PUT)
        $responseUpdate = $this->json(
            'PUT',
            'api/v1/saved_searches/' . $id,
            [
                'name' => 'Edited Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => true,
                'filters' => [
                    0 => [
                        'id' => 1,
                        'terms' => [
                            'term a',
                            'term b',
                        ]
                    ]
                ],
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
        $this->assertEquals($contentUpdate['data']['name'], 'Edited Test Saved Search');
        $this->assertEquals($contentUpdate['data']['enabled'], true);

        // edit PATCH
        $responseEdit1 = $this->json(
            'PATCH',
            'api/v1/saved_searches/' . $id,
            [
                'name' => 'Edited Test Saved Search - patch',
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
        $this->assertEquals($contentEdit1['data']['name'], 'Edited Test Saved Search - patch');
    }

    /**
     * Tests it can delete a saved search
     * 
     * @return void
     */
    public function test_it_can_delete_a_saved_search()
    {
        // create
        $response = $this->json(
            'POST',
            'api/v1/saved_searches',
            [
                'name' => 'Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => false,
                'filters' => [
                    0 => [
                        'id' => 1,
                        'terms' => [
                            'term a',
                            'term b',
                        ]
                    ]
                ],
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

        // delete      
        $response = $this->json(
            'DELETE',
            'api/v1/saved_searches/' . $content['data'],
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

    /**
     * Tests user permissions with a new saved search
     * 
     * @return void
     */
    public function test_user_can_create_update_delete_a_saved_search()
    {
        $this->authorisationUser(false);
        $nonAdminJwt = $this->getAuthorisationJwt(false);
        $headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $nonAdminJwt,
        ];

        $response = $this->json(
            'POST',
            'api/v1/saved_searches',
            [
                'name' => 'Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => false,
                'filters' => [],
            ],
            $headerNonAdmin,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $content = $response->decodeResponseJson();
        $newSearchId = $content['data'];
        
        // test admin can view saved search
        $responseGet = $this->get('api/v1/saved_searches/' . $newSearchId, [
            'Authorization' => 'bearer ' . $this->accessToken,
        ]);
        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // test admin cannot edit or delete
        $responseUpdate = $this->json(
            'PUT',
            'api/v1/saved_searches/' . $newSearchId,
            [
                'name' => 'Edited Test Saved Search',
                'search_term' => 'Some Test Query',
                'search_endpoint' => 'datasets',
                'enabled' => true,
                'filters' => []
            ],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseUpdate->assertJsonStructure([
            'message',
            'data'
        ]);
        $responseUpdate->assertStatus(500);

        $responseDelete = $this->json(
            'DELETE',
            'api/v1/saved_searches/' . $newSearchId,
            [],
            [
                'Authorization' => 'bearer ' . $this->accessToken,
            ],
        );

        $responseUpdate->assertJsonStructure([
            'message',
            'data',
        ]);
        $responseUpdate->assertStatus(500);
    }
}
