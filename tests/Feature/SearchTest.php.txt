<?php

namespace Tests\Feature;

use Config;
use App\Models\Dur;
use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use Database\Seeders\DurSeeder;
use Database\Seeders\TagSeeder;
use Tests\Traits\Authorization;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\DurHasToolSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\TypeCategorySeeder;
use MetadataManagementController as MMC;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\CollectionHasUserSeeder;
use Database\Seeders\DataProviderCollsSeeder;
use Database\Seeders\ProgrammingPackageSeeder;
use Database\Seeders\PublicationHasToolSeeder;
use Database\Seeders\ProgrammingLanguageSeeder;
use Database\Seeders\CollectionHasKeywordSeeder;
use Database\Seeders\DatasetVersionHasToolSeeder;
use Database\Seeders\CollectionHasDatasetVersionSeeder;
use Database\Seeders\DatasetVersionHasDatasetVersionSeeder;

class SearchTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_SEARCH = '/api/v1/search';

    protected $header = [];
    protected $metadata;

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
            LicenseSeeder::class,
            CategorySeeder::class,
            TypeCategorySeeder::class,
            ToolSeeder::class,
            CollectionSeeder::class,
            KeywordSeeder::class,
            CollectionHasDatasetVersionSeeder::class,
            CollectionHasKeywordSeeder::class,
            DatasetVersionHasDatasetVersionSeeder::class,
            DatasetVersionHasToolSeeder::class,
            DurSeeder::class,
            PublicationSeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            TagSeeder::class,
            PublicationHasToolSeeder::class,
            DataProviderCollsSeeder::class,
            DurHasToolSeeder::class,
            CollectionHasUserSeeder::class,
            DatasetVersionSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
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
                        'updated_at'
                    ],
                    'dataProviderColl',
                    'team' => [
                        'id',
                        'is_question_bank',
                    ],
                ],
            ],
            'aggregations',
            'elastic_total',
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
            'elastic_total',
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

        // Test search result with id not in db is not returned
        $content = $response->decodeResponseJson();
        $elasticIds = array();
        foreach ($content['data'] as $res) {
            $elasticIds[] = $res['_id'];
        }
        $this->assertTrue(!in_array('1111', $elasticIds));

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
            'elastic_total',
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

        // Test sorting by updated_at desc
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?sort=updated_at:desc', ["query" => "asthma"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'dataProviderColl',
                    'team' => [
                        'id',
                        'is_question_bank',
                    ]
                ],
            ],
            'aggregations',
            'elastic_total',
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

        // Test minimal payload for searching datasets
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/datasets" . '?view_type=mini&sort=updated_at:desc', ["query" => "asthma"], ['Accept' => 'application/json']);
        $response->assertStatus(200);

        $metadata = $response['data'][0]['metadata'];

        $this->assertTrue(isset($metadata['additional']['containsTissue']));
        if (version_compare(Config::get('metadata.GWDM.version'), "2.0", ">=")) {
            $this->assertTrue(isset($metadata['accessibility']['access']['accessServiceCategory']));
        }
        $this->assertTrue(isset($metadata['additional']['hasTechnicalMetadata']));

        $this->assertFalse(isset($metadata['coverage']));
        $this->assertFalse(isset($metadata['linkage']));
        $this->assertFalse(isset($metadata['observations']));
        $this->assertFalse(isset($metadata['structuralMetadata']));
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
                    'metadata',
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
                        'updated_at'
                    ],
                    'uploader',
                    'team_name',
                    'type_category',
                    'license',
                    'programming_language',
                    'programming_package',
                    'datasets',
                    'dataProviderColl',
                    'durTitles',
                ],
            ],
            'aggregations',
            'elastic_total',
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

        // Test search result with id not in db is not returned
        $content = $response->decodeResponseJson();
        $elasticIds = array();
        foreach ($content['data'] as $res) {
            $elasticIds[] = $res['_id'];
        }
        $this->assertTrue(!in_array('1111', $elasticIds));

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=score:asc', ["query" => "nlp"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'uploader',
                    'team_name',
                    'type_category',
                    'license',
                    'programming_language',
                    'programming_package',
                    'datasets',
                    'dataProviderColl',
                ],
            ],
            'aggregations',
            'elastic_total',
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
                    '_source',
                    'uploader',
                    'team_name',
                    'type_category',
                    'license',
                    'programming_language',
                    'programming_package',
                    'datasets',
                    'dataProviderColl',
                ],
            ],
            'aggregations',
            'elastic_total',
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

        // Test sorting by updated_at desc
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/tools" . '?sort=updated_at:desc', ["query" => "nlp"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'uploader',
                    'team_name',
                    'type_category',
                    'license',
                    'programming_language',
                    'programming_package',
                    'datasets',
                    'dataProviderColl',
                ],
            ],
            'aggregations',
            'elastic_total',
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
                        'updated_at'
                    ],
                    'name',
                    'dataProviderColl',
                ],
            ],
            'aggregations',
            'elastic_total',
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

        // Test search result with id not in db is not returned
        $content = $response->decodeResponseJson();
        $elasticIds = array();
        foreach ($content['data'] as $res) {
            $elasticIds[] = $res['_id'];
        }
        $this->assertTrue(!in_array('1111', $elasticIds));

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=score:asc', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name',
                    'dataProviderColl',
                ],
            ],
            'aggregations',
            'elastic_total',
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
                    'name',
                    'dataProviderColl',
                ],
            ],
            'aggregations',
            'elastic_total',
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/collections" . '?sort=updated_at:desc', ["query" => "nlp"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name',
                    'dataProviderColl',
                ],
            ],
            'aggregations',
            'elastic_total',
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
        Dur::query()->update(['status' => 'ACTIVE']);
        $metadata = $this->metadata;
        MMC::shouldReceive("translateDataModelType")
            ->with(json_encode($this->metadata), Config::get('metadata.GWDM.name'), Config::get('metadata.GWDM.version'))
            ->andReturnUsing(function (string $metadata) {
                return [
                    "traser_message" => "",
                    "wasTranslated" => true,
                    "metadata" => json_decode($metadata, true)["metadata"],
                    "statusCode" => "200",
                ];
            });
        $responseUpdateDataset = $this->json(
            'PUT',
            '/api/v1/datasets/1',
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_DRAFT,
            ],
            $this->header,
        );
        $metadata = $this->getMetadata();

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
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'PUT',
            '/api/v1/dur/1',
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
                        'updated_at'
                    ],
                    'organisationName',
                    'projectTitle',
                    'datasetTitles',
                    'team',
                    'dataProviderColl',
                    'toolNames',
                    'non_gateway_datasets'
                ],
            ],
            'aggregations',
            'elastic_total',
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

        $this->assertTrue($response['data'][0]['datasetTitles'][$endTitle] === 'HDR UK Papers & Preprints');

        // Test search result with id not in db is not returned
        $content = $response->decodeResponseJson();
        $elasticIds = array();
        foreach ($content['data'] as $res) {
            $elasticIds[] = $res['_id'];
        }
        $this->assertTrue(!in_array('1111', $elasticIds));

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
            'elastic_total',
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
            'elastic_total',
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

        // Test sorting by updated_at desc
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/dur" . '?sort=updated_at:desc', ["query" => "term"], ['Accept' => 'application/json']);
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
            'elastic_total',
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
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications", ["query" => "term"], ['Accept' => 'application/json']);
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
                        'year_of_publication',
                        'datasetTitles',
                    ],
                    'paper_title',
                    'abstract',
                    'authors',
                    'journal_name',
                    'year_of_publication',
                    'full_text_url',
                    'url',
                    'datasetLinkTypes',
                    'datasetVersions',
                    'collections',
                    'tools',
                    'durs',

                ],
            ],
            'aggregations',
            'elastic_total',
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

        // Test search result with id not in db is not returned
        $content = $response->decodeResponseJson();
        $elasticIds = array();
        foreach ($content['data'] as $res) {
            $elasticIds[] = $res['_id'];
        }
        $this->assertTrue(!in_array('1111', $elasticIds));

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
            'elastic_total',
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
            'elastic_total',
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
            'elastic_total',
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

        // Test federated search sorted by publication date
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications" . '?sort=year_of_publication:desc&source=FED', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_source' => [
                        'title',
                        'year_of_publication',
                    ],
                    'paper_title',
                    'abstract',
                    'authors',
                    'journal_name',
                    'year_of_publication',
                    'full_text_url',
                    'url'
                ],
            ],
            'aggregations',
            'elastic_total',
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
        $this->assertTrue($response['data'][0]['paper_title'] === 'Federated publication two');
        $this->assertTrue($response['data'][1]['paper_title'] === 'Federated publication');

        // Test federated search with array of queries
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications" . '?source=FED', ["query" => ["term", "another term"]], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_source' => [
                        'title',
                        'year_of_publication',
                    ],
                    'paper_title',
                    'abstract',
                    'authors',
                    'journal_name',
                    'year_of_publication',
                    'full_text_url',
                    'url'
                ],
            ],
            'aggregations',
            'elastic_total',
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

        // Test federated search recognises a doi and performs doi search
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/publications?source=FED", ["query" => "https://doi.org/10.3310/abcde"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_source' => [
                        'title',
                        'year_of_publication',
                    ],
                    'paper_title',
                    'abstract',
                    'authors',
                    'journal_name',
                    'year_of_publication',
                    'full_text_url',
                    'url'
                ],
            ],
            'aggregations',
            'elastic_total',
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
        $this->assertTrue($response['data'][0]['paper_title'] === 'DOI test publication');
    }

    /**
     * Search using a doi with success
     *
     * @return void
     */
    public function test_doi_search_with_success(): void
    {
        // Test federated search sorted by publication date
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/doi", ["query" => "https://doi.org/10.3310/abcde"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'title',
                'authors',
                'abstract',
                'is_preprint',
                'journal_name',
                'publication_year',
        ]]);
        $this->assertTrue($response['data']['title'] === 'DOI test publication');
        $this->assertTrue($response['data']['is_preprint'] === true);
        $this->assertNull($response['data']['publication_year']);
    }

    /**
     * Search using a query with success
     *
     * @return void
     */
    public function test_data_provider_colls_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_provider_colls", ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    '_index',
                    '_source' => [
                        'name',
                        'datasetTitles',
                        'updated_at'
                    ],
                    'id',
                    'name',
                    'img_url',
                    'datasetTitles',
                ],
            ],
            'aggregations',
            'elastic_total',
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

        // Test search result with id not in db is not returned
        $content = $response->decodeResponseJson();
        $elasticIds = array();
        foreach ($content['data'] as $res) {
            $elasticIds[] = $res['_id'];
        }
        $this->assertTrue(!in_array('123', $elasticIds));

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_provider_colls" . '?sort=score:asc', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_index',
                    '_id',
                    '_source',
                    'name',
                    'datasetTitles',
                    'id',
                    'img_url',
                    'geographicLocations',
                ],
            ],
            'aggregations',
            'elastic_total',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'Data Custodian Network One');

        // Test sorting by name
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_provider_colls" . '?sort=name:asc', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    '_index',
                    '_source',
                    'name',
                    'id',
                    'img_url',
                    'datasetTitles',
                    'geographicLocations',
                ],
            ],
            'aggregations',
            'elastic_total',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'Data Custodian Network One');

        // Test sorting by updated_at desc
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_provider_colls" . '?sort=updated_at:desc', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    '_index',
                    '_source',
                    'name',
                    'id',
                    'img_url',
                    'datasetTitles',
                    'geographicLocations',
                ],
            ],
            'aggregations',
            'elastic_total',
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
    public function test_data_provider_search_with_success(): void
    {
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_providers", ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source' => [
                        'name',
                        'datasetTitles',
                        'geographicLocations',
                        'dataType',
                        'updated_at'
                    ],
                    'name',
                    'team_logo'
                ],
            ],
            'aggregations',
            'elastic_total',
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

        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_providers" . '?sort=score:asc', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name',
                    'team_logo'
                ],
            ],
            'aggregations',
            'elastic_total',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'Third Provider');

        // Test sorting by name
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_providers" . '?sort=name:asc', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name',
                    'team_logo'
                ],
            ],
            'aggregations',
            'elastic_total',
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
        $this->assertTrue($response['data'][0]['_source']['name'] === 'Another Provider');

        // Test sorting by updated_at desc
        $response = $this->json('POST', self::TEST_URL_SEARCH . "/data_providers" . '?sort=updated_at:desc', ["query" => "term"], ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    '_id',
                    'highlight',
                    '_source',
                    'name',
                    'team_logo'
                ],
            ],
            'aggregations',
            'elastic_total',
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
