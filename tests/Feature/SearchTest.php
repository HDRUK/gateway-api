<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Team;
use App\Models\User;
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
use Database\Seeders\PublicationSeeder;
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
            PublicationSeeder::class,
        ]);

        $this->metadataUpdate = $this->getFakeUpdateDataset();
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_datasets_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets?perPage=1", ["query" => "asthma"], ['Accept' => 'application/json']);
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
                        'dataUseTitles',
                        'populationSize',
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?sort=score:asc', ["query" => "asthma"], ['Accept' => 'application/json']);   
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?sort=title:asc', ["query" => "asthma"], ['Accept' => 'application/json']); 
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?sort=created_at:desc', ["query" => "asthma"], ['Accept' => 'application/json']); 
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
     * Search for similar datasets with success
     * 
     * @return void
     */
    public function test_similar_datasets_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . '/similar/datasets', ['id' => 1], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    '_source' => [
                        'abstract',
                        'description',
                        'keywords',
                        'named_entities',
                        'publisherName',
                        'shortTitle',
                        'title',
                        'dataUseTitles',
                        'populationSize',
                        'created_at'
                    ],
                    'metadata'
                ]
            ]              
        ]);
    }

    /**
     * Search using a query with success
     * 
     * @return void
     */
    public function test_tools_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools", ["query" => "nlp"], ['Accept' => 'application/json']);
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=score:asc', ["query" => "nlp"], ['Accept' => 'application/json']);   
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=name:asc', ["query" => "nlp"], ['Accept' => 'application/json']); 
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=created_at:desc', ["query" => "nlp"], ['Accept' => 'application/json']); 
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections", ["query" => "term"], ['Accept' => 'application/json']);
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
                        'datasetTitles',
                        'created_at'
                    ],
                    'name'
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=score:asc', ["query" => "term"], ['Accept' => 'application/json']);   
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name'
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=name:asc', ["query" => "term"], ['Accept' => 'application/json']); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name'
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=created_at:desc', ["query" => "nlp"], ['Accept' => 'application/json']); 
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name'
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
        // update dataset with id 1
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $responseUpdateDataset = $this->json(
            'PUT',
            '/api/v1/datasets/' . 1,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadataUpdate,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        // update dur with id 1 to include updated dataset and another
        $mockData = [
            'datasets' => [
                0 => [
                    'id' => 1,
                    'reason' => 'something',
                    'is_locked' => 0
                ],
                1 => [
                    'id' => 2,
                    'reason' => 'something',
                    'is_locked' => 0
                ]
            ],
            'keywords' => [],
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
        ];

        $response = $this->json(
            'PUT',
            '/api/v1/dur/' . 1,
            $mockData,
            $this->header
        );
        $response->assertStatus(200);

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur", ["query" => "term"], ['Accept' => 'application/json']);
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
                        'publisherName',
                        'sector',
                        'organisationName',
                        'created_at'
                    ],
                    'organisationName',
                    'projectTitle',
                    'datasetTitles',
                    'team'
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
        $this->assertTrue($response['data'][0]['_id'] === "1");
        // Test dataset titles are alphabetical - "updated" will be at the end
        $endTitle = array_key_last($response['data'][0]['datasetTitles']);
        $this->assertTrue($response['data'][0]['datasetTitles'][$endTitle] === 'Updated HDR UK Papers & Preprints');

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur" . '?sort=score:asc', ["query" => "term"], ['Accept' => 'application/json']);   
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur" . '?sort=projectTitle:asc', ["query" => "term"], ['Accept' => 'application/json']); 
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur" . '?sort=created_at:desc', ["query" => "term"], ['Accept' => 'application/json']); 
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
    public function test_publications_search_with_success(): void
    {
        Http::fake([
            env('SEARCH_SERVICE_URL') . '/search/publications*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'hits' => [
                            0 => [
                                '_explanation' => [],
                                '_id' => '1',
                                '_index' => 'publications',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 20.0,
                                '_shard' => '[publication][0]',
                                '_source' => [
                                    'title' => 'One Data Use',
                                    'journalName' => 'A Journal',
                                    'abstract' => '',
                                    'authors' => '',
                                    'publicationDate' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'publicationType' => ['article', 'comment', 'letter'],
                                ],
                                'highlight' => [
                                    'title' => [],
                                    'abstract' => []
                                ]
                            ],
                            1 => [
                                '_explanation' => [],
                                '_id' => '2',
                                '_index' => 'data_uses',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 18.0,
                                '_shard' => '[data_uses][0]',
                                '_source' => [
                                    'title' => 'Another Publication',
                                    'journalName' => 'A Journal',
                                    'abstract' => '',
                                    'authors' => '',
                                    'publicationDate' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'publicationType' => ['article', 'comment', 'letter'],
                                ],
                                'highlight' => [
                                    'laySummary' => []
                                ]
                            ],
                            2 => [
                                '_explanation' => [],
                                '_id' => '3',
                                '_index' => 'data_uses',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[data_uses][0]',
                                '_source' => [
                                    'title' => 'Third Publication',
                                    'journalName' => 'A Journal',
                                    'abstract' => '',
                                    'authors' => '',
                                    'publicationDate' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'publicationType' => ['article', 'comment', 'letter'],
                                ],
                                'highlight' => [
                                    'laySummary' => []
                                ]
                            ]
                        ]
                    ],
                    'aggregations' => []
                ],
                200,
                ['application/json']
            )
        ]);

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications", ["query" => "term"], ['Accept' => 'application/json']);
        dd($response);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source' => [
                        'title',
                        'journalName',
                        'abstract',
                        'authors',
                        'publicationDate',
                        'datasetTitles',
                        'created_at'
                    ],
                    'paper_title',
                    'abstract',
                    'authors',
                    'journal_name',
                    'year_of_publication'
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
        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications" . '?sort=score:asc', ["query" => "term"], ['Accept' => 'application/json']);   
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
        $this->assertTrue($response['data'][0]['_source']['title'] === 'Third Publication');

        // Test sorting by dataset name (shortTitle)        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications" . '?sort=title:asc', ["query" => "term"], ['Accept' => 'application/json']); 
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
        $this->assertTrue($response['data'][0]['_source']['title'] === 'Another Publication');

        // Test sorting by created_at desc        
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications" . '?sort=created_at:desc', ["query" => "term"], ['Accept' => 'application/json']); 
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