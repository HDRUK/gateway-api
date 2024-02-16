<?php

namespace Tests\Feature;

use Tests\TestCase;
use Database\Seeders\DurSeeder;
use Tests\Traits\Authorization;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Illuminate\Support\Facades\Http;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\CollectionHasDatasetSeeder;
use Database\Seeders\CollectionHasKeywordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SearchTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL_SEARCH = '/api/v1/search';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            TeamHasUserSeeder::class,
            KeywordSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            ToolSeeder::class,
            CollectionSeeder::class,
            KeywordSeeder::class,
            CollectionHasDatasetSeeder::class,
            CollectionHasKeywordSeeder::class,
            DurSeeder::class,
        ]);
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_datasets_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets?perPage=1", ["query" => "asthma"], $this->header);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source' => [
                        'abstract',
                        'description',
                        'keywords',
                        'named_entities',
                        'publisherName',
                        'shortTitle',
                        'title',
                        'created_at'
                    ],
                ],
            ],
            'aggregations',
            'current_page',
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?sort=score:asc', ["query" => "asthma"], $this->header);   
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['shortTitle'] === 'Third asthma dataset');

        // Test sorting by dataset name (shortTitle)        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?sort=title:asc', ["query" => "asthma"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['shortTitle'] === 'Another asthma dataset');

        // Test sorting by created_at desc        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?sort=created_at:desc', ["query" => "asthma"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_id'] === '1');
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_tools_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools", ["query" => "nlp"], $this->header);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source' => [
                        'category',
                        'description',
                        'name',
                        'tags',
                        'created_at'
                    ],
                ],
            ],
            'aggregations',
            'current_page',
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=score:asc', ["query" => "nlp"], $this->header);   
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'C tool');

        // Test sorting by dataset name (shortTitle)        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=name:asc', ["query" => "nlp"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'A tool');

        // Test sorting by created_at desc        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=created_at:desc', ["query" => "nlp"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_id'] === '1');
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_collections_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections", ["query" => "term"], $this->header);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source' => [
                        'description',
		                'name',
		                'keywords',
                        'relatedObjects' => [
                            'keywords',
		                    'title',
		                    'name',
		                    'description'
                        ],
                        'created_at'
                    ],
                ],
            ],
            'aggregations',
            'current_page',
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=score:asc', ["query" => "term"], $this->header);   
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'Third Collection');

        // Test sorting by dataset name (shortTitle)        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=name:asc', ["query" => "term"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'Another Collection');

        // Test sorting by created_at desc        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=created_at:desc', ["query" => "nlp"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_id'] === '1');
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_data_uses_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur", ["query" => "term"], $this->header);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source' => [
                        'projectTitle',
                        'laySummary',
                        'publicBenefitStatement',
                        'technicalSummary',
                        'fundersAndSponsors',
                        'datasetTitles',
                        'keywords',
                        'created_at'
                    ],
                ],
            ],
            'aggregations',
            'current_page',
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur" . '?sort=score:asc', ["query" => "term"], $this->header);   
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['projectTitle'] === 'Third Data Use');

        // Test sorting by dataset name (shortTitle)        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur" . '?sort=projectTitle:asc', ["query" => "term"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_source']['projectTitle'] === 'Another Data Use');

        // Test sorting by created_at desc        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur" . '?sort=created_at:desc', ["query" => "term"], $this->header); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source'
                ],
            ],
            'aggregations',
            'current_page',
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
        $this->assertTrue($response['data'][0]['_id'] === '1');
    }
}