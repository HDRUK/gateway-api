<?php

namespace Tests\Traits;


use Database\Seeders\SectorSeeder;
use Illuminate\Support\Facades\Http;

use MetadataManagementController AS MMC;

use Tests\Traits\Authorization;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Nyholm\Psr7\Response;

trait MockExternalApis
{

    use Authorization;
    private $dataset = null;
    private $datasetUpdate = null;
    protected $header = [];

    // Changed visibility. Private functions in shared trait is frowned upon
    public function getFakeDataset()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }


    public function getFakeDatasetNew()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1p1_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }

    // Changed visibility. Private functions in shared trait is frowned upon
    public function getFakeUpdateDataset()
    {
        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min_update.json', 0, null);
        $json = json_decode($jsonFile, true);

        return $json;
    }
    
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            SectorSeeder::class,
        ]);
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];

        // Define mock client and fake response for elasticsearch service
        $mockElastic = new Client();

        $elasticClient = ClientBuilder::create()
            ->setHttpClient($mockElastic)
            ->build();

        // This is a PSR-7 response
        // Mock two responses, one for creating a dataset, another for deleting
        $createResponse = new Response(
            200, 
            [Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            'Document created'
        );
        $deleteResponse = new Response(
            200, 
            [Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME],
            'Document deleted'
        );

        // Stack the responses expected in the create/archive/delete dataset test
        // create -> soft delete/archive -> unarchive -> permanent delete
        for ($i=0; $i < 100; $i++) {
            $mockElastic->addResponse($createResponse);
        }

        for ($i=0; $i < 100; $i++) {
            $mockElastic->addResponse($deleteResponse);
        }

        $this->testElasticClient = $elasticClient;

        Http::fake([
            'ted*' => Http::response(
                ['id' => 11, 'extracted_terms' => ['test', 'fake']], 
                201,
                ['application/json']
            )
        ]);

        // Mock the search service - datasets
        Http::fake([
            '*search*datasets*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
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
                                    'dataUseTitles' => []
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
                                    'dataUseTitles' => []
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
                                    'dataUseTitles' => []
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
            '*search*tools*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
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
            '*search*collections*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
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
                                    'relatedObjects' => [
                                        'keywords' => '',
                                        'title' => '',
                                        'name' => '',
                                        'description' => ''
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
                                '_index' => 'collections',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 18.0,
                                '_shard' => '[collections][0]',
                                '_source' => [
                                    'description' => 'a gateway collection',
                                    'name' => 'Another Collection',
                                    'keywords' => 'some, useful, keywords',
                                    'relatedObjects' => [
                                        'keywords' => '',
                                        'title' => '',
                                        'name' => '',
                                        'description' => ''
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
                                '_index' => 'collections',
                                '_node' => 'abcd-123-efgh',
                                '_score' => 16.0,
                                '_shard' => '[collections][0]',
                                '_source' => [
                                    'description' => 'a gateway collection',
                                    'name' => 'Third Collection',
                                    'keywords' => 'some, useful, keywords',
                                    'relatedObjects' => [
                                        'keywords' => '',
                                        'title' => '',
                                        'name' => '',
                                        'description' => ''
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
        
        // Mock the search service - data uses
        Http::fake([
            '*search*dur*' => Http::response(
                [
                    'took' => 1000,
                    'timed_out' => false,
                    '_shards' => [],
                    'hits' => [
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
                                    'keywords' => ['some', 'useful', 'keywords']
                                ],
                                'highlight' => [
                                    'laySummary' => []
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
                                    'projectTitle' => 'Another Data Use',
                                    'laySummary' => 'a gateway data use',
                                    'publicBenefitStatement' => '',
                                    'technicalSummary' => '',
                                    'fundersAndSponsors' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'keywords' => ['some', 'useful', 'keywords']
                                ],
                                'highlight' => [
                                    'laySummary' => []
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
                                    'projectTitle' => 'Third Data Use',
                                    'laySummary' => 'a gateway data use',
                                    'publicBenefitStatement' => '',
                                    'technicalSummary' => '',
                                    'fundersAndSponsors' => '',
                                    'datasetTitles' => ['some', 'dataset', 'title'],
                                    'keywords' => ['some', 'useful', 'keywords']
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

        // Mock the search service - filters
        Http::fake([
            '*search*filters*' => Http::response(
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
                                'containsTissue' => [
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

        // Mock the MMC getElasticClient method to return the mock client
        // makePartial so other MMC methods are not mocked
        MMC::shouldReceive('getElasticClient')->andReturn($this->testElasticClient);
        MMC::shouldReceive("translateDataModelType")->andReturnUsing(function(string $metadata){
            return [
                "traser_message" => "",
                "wasTranslated" => true,
                "metadata" => json_decode($metadata,true)["metadata"],
                "statusCode" => "200",
            ];
        });
        MMC::shouldReceive("validateDataModelType")->andReturn(true);
        MMC::makePartial();

        $this->dataset_store = [];

        Http::fake([
            env('MJML_RENDER_URL') => Http::response(
                ["html"=>"<html>content</html>"], 
                201,
                ['application/json']
            )
        ]);

    }

    // Count requests made to the elastic mock client
    public function countElasticClientRequests(object $client): int
    {
        return count($client->getTransport()->getClient()->getRequests());
    }

}