<?php

namespace Tests\Traits;

use Config;
use Http\Mock\Client;
use Nyholm\Psr7\Response;
use App\Jobs\LinkageExtraction;
use App\Jobs\TermExtraction;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use MetadataManagementController as MMC;

trait MockExternalApis
{
    use Authorization;
    private $dataset = null;
    private $datasetUpdate = null;
    protected $header = [];

    // Changed visibility. Private functions in shared trait is frowned upon
    public function getMetadataV1p0()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }


    public function getMetadataV1p1()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1p1_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }

    // Changed visibility. Private functions in shared trait is frowned upon
    public function getMetadataV2p0()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v2p0_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }

    public function getMetadata()
    {
        $version = Config::get('metadata.GWDM.version');
        switch (true) {
            case version_compare($version, "1.0", "<="):
                return $this->getMetadataV1p0();

            case version_compare($version, "1.2", "<="):
                #note: v1.1 and v1.2 were not that different so can use this example metadata
                return $this->getMetadataV1p1();

            case version_compare($version, "2.0", "<="):
                return $this->getMetadataV2p0();
        }
    }

    public function getPublicSchema()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/hdruk_3p0p0_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }

    public function setUp(): void
    {
        parent::setUp();

        Queue::fake([
            LinkageExtraction::class,
            TermExtraction::class
        ]);

        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->currentUser = $this->getUserFromJwt($jwt);
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        Mail::fake();


        // This is a PSR-7 response
        // Mock two responses, one for creating a dataset, another for deleting
        $elasticBaseUrl = Config::get('database.connections.elasticsearch.host');

        // Define the URL pattern for the index document endpoint
        $indexDocumentUrlPattern = $elasticBaseUrl . '/*/_doc/*';

        // Fake the HTTP request
        Http::fake([
            $indexDocumentUrlPattern => function ($request) {
                return Http::response('Document created', 200, ['application/json']);
            },
        ]);


        $deleteDocumentsUrlPattern = $elasticBaseUrl . '/*/_delete_by_query';

        // Fake the HTTP request
        Http::fake([
            $deleteDocumentsUrlPattern => function ($request) {
                return Http::response('Document deleted', 200, ['application/json']);
            },
        ]);

        Http::fake([
            config("ted.url") . "/datasets" => Http::response(
                ['id' => 11, 'extracted_terms' => ['test', 'fake']],
                201,
                ['application/json']
            )
        ]);

        Http::fake([
            config("ted.url") . "/summary" => Http::response(
                ['id' => 11, 'extracted_terms' => ['test', 'fake']],
                201,
                ['application/json']
            )
        ]);

        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/filters' => Http::response(
                [
                    200,
                    ['application/json']
                ]
            )
        ]);

        // Mock the search service - datasets
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/datasets*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'total' => [
                            'value' => 3
                        ],
                        'hits' => [
                            0 => [
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
                                    'publisherName' => '',
                                    'shortTitle' => 'Asthma dataset',
                                    'title' => 'Asthma dataset',
                                    'dataUseTitles' => [],
                                    'populationSize' => 1000,
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            1 => [
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
                                    'publisherName' => '',
                                    'shortTitle' => 'Another asthma dataset',
                                    'title' => 'Another asthma dataset',
                                    'dataUseTitles' => [],
                                    'populationSize' => 1000,
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            2 => [
                                '_explanation' => [],
                                '_id' => '3',
                                '_index' => 'datasets',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[datasets][0]',
                                '_source' => [
                                    'abstract' => '',
                                    'description' => '',
                                    'keywords' => '',
                                    'named_entities' => [],
                                    'publisherName' => '',
                                    'shortTitle' => 'Third asthma dataset',
                                    'title' => 'Third asthma dataset',
                                    'dataUseTitles' => [],
                                    'populationSize' => 1000,
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            3 => [
                                '_explanation' => [],
                                '_id' => '1111',
                                '_index' => 'datasets',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 15.0,
                                '_shard' => '[datasets][0]',
                                '_source' => [
                                    'abstract' => '',
                                    'description' => '',
                                    'keywords' => '',
                                    'named_entities' => [],
                                    'publisherName' => '',
                                    'shortTitle' => 'Fourth asthma dataset',
                                    'title' => 'Fourth asthma dataset',
                                    'dataUseTitles' => [],
                                    'populationSize' => 1000,
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ]
                        ]
                    ],
                    'aggregations' => [
                        'publisherName' => [
                            'buckets' => [
                                0 => [
                                    'doc_count' => 10,
                                    'key' => 'A PUBLISHER'
                                ]
                            ]
                        ]
                    ]
                ],
                200,
                ['application/json']
            )
        ]);

        // Mock the search service - similar datasets
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/similar/datasets*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'total' => [
                            'value' => 3
                        ],
                        'hits' => [
                            0 => [
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
                                    'publisherName' => '',
                                    'shortTitle' => 'Asthma dataset',
                                    'title' => 'Asthma dataset',
                                    'dataUseTitles' => [],
                                    'populationSize' => 1000,
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            1 => [
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
                                    'publisherName' => '',
                                    'shortTitle' => 'Another asthma dataset',
                                    'title' => 'Another asthma dataset',
                                    'dataUseTitles' => [],
                                    'populationSize' => 1000,
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            2 => [
                                '_explanation' => [],
                                '_id' => '3',
                                '_index' => 'datasets',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[datasets][0]',
                                '_source' => [
                                    'abstract' => '',
                                    'description' => '',
                                    'keywords' => '',
                                    'named_entities' => [],
                                    'publisherName' => '',
                                    'shortTitle' => 'Third asthma dataset',
                                    'title' => 'Third asthma dataset',
                                    'dataUseTitles' => [],
                                    'populationSize' => 1000,
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ]
                        ]
                    ],
                    'aggregations' => [
                        'publisherName' => [
                            'buckets' => [
                                0 => [
                                    'doc_count' => 10,
                                    'key' => 'A PUBLISHER'
                                ]
                            ]
                        ]
                    ]
                ],
                200,
                ['application/json']
            )
        ]);

        // Mock the search service - tools
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/tools*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'total' => [
                            'value' => 3
                        ],
                        'hits' => [
                            0 => [
                                '_explanation' => [],
                                '_id' => '1',
                                '_index' => 'tools',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 20.0,
                                '_shard' => '[tools][0]',
                                '_source' => [
                                    'category' => 'NLP System',
                                    'description' => 'An NLP tool',
                                    'name' => 'B tool',
                                    'tags' => [
                                        'nlp',
                                        'machine learning'
                                    ]
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            1 => [
                                '_explanation' => [],
                                '_id' => '2',
                                '_index' => 'tools',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 18.0,
                                '_shard' => '[tools][0]',
                                '_source' => [
                                    'category' => 'NLP System',
                                    'description' => 'Other NLP tool',
                                    'name' => 'A tool',
                                    'tags' => [
                                        'nlp',
                                        'machine learning'
                                    ]
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            2 => [
                                '_explanation' => [],
                                '_id' => '3',
                                '_index' => 'tools',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[tools][0]',
                                '_source' => [
                                    'category' => 'NLP System',
                                    'description' => 'Yet another NLP tool',
                                    'name' => 'C tool',
                                    'tags' => [
                                        'nlp',
                                        'machine learning'
                                    ]
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            3 => [
                                '_explanation' => [],
                                '_id' => '1111',
                                '_index' => 'tools',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[tools][0]',
                                '_source' => [
                                    'category' => 'NLP System',
                                    'description' => 'Yet another NLP tool',
                                    'name' => 'D tool',
                                    'tags' => [
                                        'nlp',
                                        'machine learning'
                                    ]
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
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

        // Mock the search service - collections
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/collections*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'total' => [
                            'value' => 3
                        ],
                        'hits' => [
                            0 => [
                                '_explanation' => [],
                                '_id' => '1',
                                '_index' => 'collections',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 20.0,
                                '_shard' => '[collections][0]',
                                '_source' => [
                                    'description' => 'a gateway collection',
                                    'name' => 'One Collection',
                                    'keywords' => 'some, useful, keywords',
                                    'datasetTitles' => ['some', 'dataset', 'titles']
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            1 => [
                                '_explanation' => [],
                                '_id' => '2',
                                '_index' => 'collections',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 18.0,
                                '_shard' => '[collections][0]',
                                '_source' => [
                                    'description' => 'a gateway collection',
                                    'name' => 'Another Collection',
                                    'keywords' => 'some, useful, keywords',
                                    'datasetTitles' => ['some', 'dataset', 'titles']
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            2 => [
                                '_explanation' => [],
                                '_id' => '3',
                                '_index' => 'collections',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[collections][0]',
                                '_source' => [
                                    'description' => 'a gateway collection',
                                    'name' => 'Third Collection',
                                    'keywords' => 'some, useful, keywords',
                                    'datasetTitles' => ['some', 'dataset', 'titles']
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
                                ]
                            ],
                            3 => [
                                '_explanation' => [],
                                '_id' => '1111',
                                '_index' => 'collections',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[collections][0]',
                                '_source' => [
                                    'description' => 'a gateway collection',
                                    'name' => 'Fourth Collection',
                                    'keywords' => 'some, useful, keywords',
                                    'datasetTitles' => ['some', 'dataset', 'titles']
                                ],
                                'highlight' => [
                                    'abstract' => [],
                                    'description' => []
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

        // Mock the search service - data uses
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/dur*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'total' => [
                            'value' => 3
                        ],
                        'hits' => [
                            0 => [
                                '_explanation' => [],
                                '_id' => '1',
                                '_index' => 'data_uses',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 20.0,
                                '_shard' => '[data_uses][0]',
                                '_source' => [
                                    'projectTitle' => 'One Data Use',
                                    'laySummary' => 'a gateway data use',
                                    'publicBenefitStatement' => '',
                                    'technicalSummary' => '',
                                    'fundersAndSponsors' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'keywords' => ['some', 'useful', 'keywords'],
                                    'sector' => 'Academia',
                                    'publisherName' => 'A Publisher',
                                    'organisationName' => 'An Organisation'
                                ],
                                'highlight' => [
                                    'laySummary' => []
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
                                    'projectTitle' => 'Another Data Use',
                                    'laySummary' => 'a gateway data use',
                                    'publicBenefitStatement' => '',
                                    'technicalSummary' => '',
                                    'fundersAndSponsors' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'keywords' => ['some', 'useful', 'keywords'],
                                    'sector' => 'Academia',
                                    'publisherName' => 'A Publisher',
                                    'organisationName' => 'An Organisation'
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
                                    'projectTitle' => 'Third Data Use',
                                    'laySummary' => 'a gateway data use',
                                    'publicBenefitStatement' => '',
                                    'technicalSummary' => '',
                                    'fundersAndSponsors' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'keywords' => ['some', 'useful', 'keywords'],
                                    'sector' => 'Academia',
                                    'publisherName' => 'A Publisher',
                                    'organisationName' => 'An Organisation'
                                ],
                                'highlight' => [
                                    'laySummary' => []
                                ]
                            ],
                            3 => [
                                '_explanation' => [],
                                '_id' => '1111',
                                '_index' => 'data_uses',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[data_uses][0]',
                                '_source' => [
                                    'projectTitle' => 'Fourth Data Use',
                                    'laySummary' => 'a gateway data use',
                                    'publicBenefitStatement' => '',
                                    'technicalSummary' => '',
                                    'fundersAndSponsors' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'keywords' => ['some', 'useful', 'keywords'],
                                    'sector' => 'Academia',
                                    'publisherName' => 'A Publisher',
                                    'organisationName' => 'An Organisation'
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

        // Mock the search service - publications
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/publications*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'total' => [
                            'value' => 3
                        ],
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
                            ],
                            3 => [
                                '_explanation' => [],
                                '_id' => '1111',
                                '_index' => 'data_uses',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[data_uses][0]',
                                '_source' => [
                                    'title' => 'Fourth Publication',
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

        // Mock the search service - publications
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/federated_papers/field_search*' => Http::response(
                [
                    'version' => '10.1',
                    'hitCount' => 2,
                    'request' => [
                        'queryString' => 'DOI:10.123/abc',
                        'resultType' => 'core',
                        'cursorMark' => '*',
                        'pageSize' => 25,
                        'sort' => '',
                        'synonym' => false
                    ],
                    'resultList' => [
                        'result' => [
                            0 => [
                                'id' => '0000000',
                                'source' => 'MED',
                                'pmid' => '000000',
                                'pmcid' => 'PMC000000',
                                'fullTextIdList' => [
                                    'fullTextId' => [
                                        0 => 'PMC000000'
                                    ]
                                ],
                                'doi' => '10.123/abc',
                                'title' => 'Federated publication',
                                'authorString' => 'Monday A, Tuesday B, Wednesday C',
                                'journalInfo' => [
                                    'journal' => [
                                        'title' => 'Journal of Health'
                                    ]
                                ],
                                'pubYear' => '2020',
                                'abstractText' => 'A longer description of the paper',
                                'pubTypeList' => [
                                    'pubType' => [
                                        'research-article',
                                        'Journal Article'
                                    ]
                                ],
                                'fullTextUrlList' => [
                                    'fullTextUrl' => [
                                        0 => [
                                            'url' => 'https://doi.org/10.123/abc'
                                        ]
                                    ]
                                ]
                            ],
                            1 => [
                                'id' => '0000001',
                                'source' => 'MED',
                                'pmid' => '000001',
                                'pmcid' => 'PMC000001',
                                'fullTextIdList' => [
                                    'fullTextId' => [
                                        0 => 'PMC000001'
                                    ]
                                ],
                                'doi' => '10.123/abc',
                                'title' => 'Federated publication two',
                                'authorString' => 'Monday A, Tuesday B, Wednesday C',
                                'journalInfo' => null,
                                'pubYear' => '2022',
                                'abstractText' => 'A longer description of the paper',
                                'pubTypeList' => [
                                    'pubType' => [
                                        'research-article',
                                        'Journal Article'
                                    ]
                                ],
                                'fullTextUrlList' => [
                                    'fullTextUrl' => [
                                        0 => [
                                            'url' => 'https://doi.org/10.456/abc'
                                        ]
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

        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/federated_papers/doi' => Http::response(
                [
                "hitCount" => 1,
                "resultList" => [
                    "result" => [
                        0 => [
                            "id" => "PPR885146",
                            "doi" => "10.3310/abcde",
                            "title" => "DOI test publication",
                            "authorString" => "",
                            "journalInfo" => null,
                            "pubYear" => "2024",
                            "abstractText" => "",
                            'fullTextUrlList' => [
                                'fullTextUrl' => [
                                    0 => [
                                        'url' => 'https://doi.org/10.123/abc'
                                    ]
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

        // Mock the search service - data providers
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/data_providers*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
                        'total' => [
                            'value' => 3
                        ],
                        'hits' => [
                            0 => [
                                '_explanation' => [],
                                '_id' => '1',
                                '_index' => 'dataprovider',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 20.0,
                                '_shard' => '[dataprovider][0]',
                                '_source' => [
                                    'name' => 'One Provider',
                                    'datasetTitles' => ['some', 'dataset', 'titles'],
                                    'geographicLocations' => ['Scotland', 'Wales'],
                                    'dataType' => ['Healthdata']
                                ],
                                'highlight' => null,
                            ],
                            1 => [
                                '_explanation' => [],
                                '_id' => '2',
                                '_index' => 'dataprovider',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 18.0,
                                '_shard' => '[dataprovider][0]',
                                '_source' => [
                                    'name' => 'Another Provider',
                                    'datasetTitles' => ['some', 'dataset', 'titles'],
                                    'geographicLocations' => ['Scotland', 'Wales'],
                                    'dataType' => ['Healthdata']
                                ],
                                'highlight' => null,
                            ],
                            2 => [
                                '_explanation' => [],
                                '_id' => '3',
                                '_index' => 'dataprovider',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[dataprovider][0]',
                                '_source' => [
                                    'name' => 'Third Provider',
                                    'datasetTitles' => ['some', 'dataset', 'titles'],
                                    'geographicLocations' => ['Scotland', 'Wales'],
                                    'dataType' => ['Healthdata']
                                ],
                                'highlight' => null,
                            ],
                            3 => [
                                '_explanation' => [],
                                '_id' => '1111',
                                '_index' => 'dataprovider',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[dataprovider][0]',
                                '_source' => [
                                    'name' => 'Fourth Provider',
                                    'datasetTitles' => ['some', 'dataset', 'titles'],
                                    'geographicLocations' => ['Scotland', 'Wales'],
                                    'dataType' => ['Healthdata']
                                ],
                                'highlight' => null,
                            ]
                        ]
                    ],
                    'aggregations' => []
                ],
                200,
                ['application/json']
            )
        ]);

        // Mock the search service - filters
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/filters*' => Http::response(
                [
                    'filters' => [
                        0 => [
                            'dataset' => [
                                'publisherName' => [
                                    'buckets' => [
                                        0 => [
                                            'doc_count' => 10,
                                            'key' => 'publisher1'
                                        ],
                                        1 => [
                                            'doc_count' => 5,
                                            'key' => 'publisher2'
                                        ],
                                        2 => [
                                            'doc_count' => 1,
                                            'key' => 'publisher3'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        1 => [
                            'dataset' => [
                                'containsBioSamples' => [
                                    'buckets' => [
                                        0 => [
                                            'doc_count' => 10,
                                            'key' => true
                                        ],
                                        1 => [
                                            'doc_count' => 5,
                                            'key' => false
                                        ]
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

        // Mock the search service - data providers
        Http::fake([
            env('SEARCH_SERVICE_URL', 'http://localhost:8003') . '/search/data_custodian_networks*' => Http::response(
                [
                    'took' => 4,
                    'timed_out' => false,
                    '_shards' => [
                        'total' => 1,
                        'successful' => 1,
                        'skipped' => 0,
                        'failed' => 0
                    ],
                    'hits' => [
                        'total' => [
                            'value' => 3,
                            'relation' => 'eq'
                        ],
                        'max_score' => 1.0,
                        'hits' => [
                            [
                                '_index' => 'datacustodiannetwork',
                                '_id' => '1',
                                '_score' => 1.0,
                                '_ignored' => [
                                    'summary.keyword'
                                ],
                                '_source' => [
                                    'name' => 'Data Custodian Network One',
                                    'summary' => 'Data Custodian Name One - Summary',
                                    'publisherNames' => ['Publisher Name One'],
                                    'datasetTitles' => ['Dataset Title One'],
                                    'durTitles' => ['Dur Title One'],
                                    'toolNames' => ['Tool Name One'],
                                    'publicationTitles' => ['Publication Name One'],
                                    'collectionNames' => ['Collection Name One']
                                ]
                            ],
                            [
                                '_index' => 'datacustodiannetwork',
                                '_id' => '12',
                                '_score' => 1.0,
                                '_ignored' => [
                                    'summary.keyword'
                                ],
                                '_source' => [
                                    'name' => 'Data Custodian Network Two',
                                    'summary' => 'Data Custodian Name Two - Summary',
                                    'publisherNames' => ['Publisher Name Two'],
                                    'datasetTitles' => ['Dataset Title Two'],
                                    'durTitles' => ['Dur Title Two'],
                                    'toolNames' => ['Tool Name Two'],
                                    'publicationTitles' => ['Publication Name Two'],
                                    'collectionNames' => ['Collection Name Two']
                                ]
                            ],
                            [
                                '_index' => 'datacustodiannetwork',
                                '_id' => '123',
                                '_score' => 1.0,
                                '_ignored' => [
                                    'summary.keyword'
                                ],
                                '_source' => [
                                    'name' => 'Data Custodian Network Three',
                                    'summary' => 'Data Custodian Name Three - Summary',
                                    'publisherNames' => ['Publisher Name Three'],
                                    'datasetTitles' => ['Dataset Title Three'],
                                    'durTitles' => ['Dur Title Three'],
                                    'toolNames' => ['Tool Name Three'],
                                    'publicationTitles' => ['Publication Name Three'],
                                    'collectionNames' => ['Collection Name Three']
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

        Http::fake([
            env('CLAMAV_API_URL', 'http://clamav:3001') . '*' => Http::response(
                [
                    'isError' => false,
                    'isInfected' => false,
                    'file' => '1716469707_test_file.csv',
                    'viruses' => [],
                ],
                200,
                ['application/json']
            )
        ]);


        MMC::shouldReceive("translateDataModelType")
           ->andReturnUsing(function (
               string $dataset,
               string $outputSchema,
               string $outputVersion,
               ?string $inputSchema = null,
               ?string $inputVersion = null,
               bool $validateInput = true,
               bool $validateOutput = true,
               ?string $subsection = null
           ) {
               $metadata = json_decode($dataset, true)["metadata"];
               //mock translating alternative schemas via traser - just give it a new GWDM metadata

               if (!array_key_exists("required", $metadata) || (!is_null($inputSchema) && $inputSchema !== 'GWDM')) {
                   $metadata = $this->getMetadata();
               }
               return [
                   "traser_message" => "",
                   "wasTranslated" => true,
                   "metadata" => $metadata,
                   "statusCode" => "200",
               ];
           });
        MMC::shouldReceive("validateDataModelType")->andReturn(true);
        MMC::makePartial();

        $this->dataset_store = [];

        // Removed for now, as the email service test contains its own mock
        // for mjml.
        // Http::fake([
        //     env('MJML_RENDER_URL') => Http::response(
        //         ["html" => "<html>content</html>"],
        //         201,
        //         ['application/json']
        //     )
        // ]);

        Http::fake([
            env('GMI_SERVICE_URL').'*' => Http::response(
                ['message' => 'success'],
                200,
                ['application/json']
            )
        ]);

        Http::fake([
            // DELETE
            "http://hub.local/contacts/v1/contact/vid/*" => function ($request) {
                if ($request->method() === 'DELETE') {
                    return Http::response([], 200);
                }
            },

            // GET (by vid)
            "http://hub.local/contacts/v1/contact/vid/*/profile" => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response(['vid' => 12345, 'properties' => []], 200);
                } elseif ($request->method() === 'POST') {
                    return Http::response([], 204);
                }
            },

            // GET (by email)
            "http://hub.local/contacts/v1/contact/email/*/profile" => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response(['vid' => 12345], 200);
                }
            },

            // POST (create contact)
            'http://hub.local/contacts/v1/contact' => function ($request) {
                if ($request->method() === 'POST') {
                    return Http::response(['vid' => 12345], 200);
                }
            },
        ]);
    }

    // Count requests made to the elastic mock client
    public function countElasticClientRequests(object $client): int
    {
        return count($client->getTransport()->getClient()->getRequests());
    }
}
