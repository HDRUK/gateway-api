<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL_SEARCH = '/api/v1/search';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_search_with_success(): void
    {
        Http::fake([
            'search-service*' => Http::response(
                [
                    'collections' => [],
                    'tools' => [],
                    'datasets' => [
                        'took' => 1000,
                        'timed_out' => false,
                        '_shards' => [],
                        'hits' => [
                            'hits' => [
                                [
                                    '_explanation' => [],
                                    '_id' => '1',
                                    '_index' => 'datasets',
                                    '_node' => 'abcd-123-efgh',
                                    '_score' => 20.0,
                                    '_shard' => '[datasets][0]',
                                    '_source' => [
                                        'abstract' => '',
                                        'description' => '',
                                        'keywords' => '',
                                        'named_entities' => [],
                                        'publisher_name' => '',
                                        'shortTitle' => 'Asthma dataset',
                                        'title' => 'Asthma dataset'
                                    ],
                                    '_explanation' => [],
                                    '_id' => '2',
                                    '_index' => 'datasets',
                                    '_node' => 'abcd-123-efgh',
                                    '_score' => 18.0,
                                    '_shard' => '[datasets][0]',
                                    '_source' => [
                                        'abstract' => '',
                                        'description' => '',
                                        'keywords' => '',
                                        'named_entities' => [],
                                        'publisher_name' => '',
                                        'shortTitle' => 'Another asthma dataset',
                                        'title' => 'Another asthma dataset'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ], 
                200,
                ['application/json']
            )
        ]);

        $response = $this->json('GET', self::TEST_URL_SEARCH, ["query" => "asthma"], $this->header);        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'datasets',
                'collections',
                'tools'
            ]
        ]);
    }
}