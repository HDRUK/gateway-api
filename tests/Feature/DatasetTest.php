<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatasetTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL_DATASET = '/api/v1/datasets';
    const TEST_URL_TEAM = 'api/v1/teams';
    const TEST_URL_NOTIFICATION = 'api/v1/notifications';
    const TEST_URL_USER = 'api/v1/users';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    /**
     * Get All Datasets with success
     * 
     * @return void
     */
    public function test_get_all_datasets_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL_DATASET, [], $this->header);

        $response->assertJsonStructure([
            'current_page',
            'data',
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
        $response->assertStatus(200);
    }

    /**
     * Get Dataset by Id with success
     * 
     * @return void
     */
    public function test_get_one_dataset_by_id_with_success(): void
    {
        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => 'Some@email.com',
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId = $contentCreateTeam['data'];

        // create user
        $responseCreateUser = $this->json(
            'POST',
            self::TEST_URL_USER,
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => 'firstname.lastname.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 75697342,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234566,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header,
        );
        $responseCreateUser->assertStatus(201);
        $contentCreateUser = $responseCreateUser->decodeResponseJson();
        $userId = $contentCreateUser['data'];

        // create dataset
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'label' => 'label dataset ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'data' => $this->getFakeDataset(),
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // get one dataset
        $responseGetOne = $this->json('GET', self::TEST_URL_DATASET . '/' . $datasetId, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data'
        ]);
        $responseGetOne->assertStatus(200);

        // delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    /**
     * Create new Dataset with success
     * 
     * @return void
     */
    public function test_create_delete_dataset_with_success(): void
    {
        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => 'some@email.com',
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();  
        $teamId = $contentCreateTeam['data'];

        // create user
        $responseCreateUser = $this->json(
            'POST',
            self::TEST_URL_USER,
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => 'firstname.lastname.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => 75697342,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234566,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header,
        );
        $responseCreateUser->assertStatus(201);
        $contentCreateUser = $responseCreateUser->decodeResponseJson();
        $userId = $contentCreateUser['data'];

        // create dataset
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'label' => 'label dataset ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'short_description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                'data' => $this->getFakeDataset(),
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json('DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    private function getFakeDataset()
    {
        return [
            'datasetv2' => [
                'identifier' => 'bd18eb49-9789-4ed3-a6c1-7b4f851ec2e7',
                'version' => '1.0.0',
                'issued' => '12/04/2022',
                'modified' => '12/04/2022',
                'revisions' => [],
                'summary' => [
                    'title' => htmlentities(fake()->paragraph(), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'abstract' => htmlentities(implode(" ", fake()->paragraphs(1, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'publisher' => [
                        'identifier' => '607db9c5e1f9d3704d570d5f',
                        'name' => 'PIONEER',
                        'logo' => '',
                        'description' => '',
                        'contactPoint' => [],
                        'memberOf' => 'HUB',
                        'accessRights' => [],
                        'deliveryLeadTime' => '',
                        'accessService' => '',
                        'accessRequestCost' => '',
                        'dataUseLimitation' => [],
                        'dataUseRequirements' => []
                    ],
                    'contactPoint' => 'PIONEER@UHB.NHS.UK',
                    'keywords' => [
                        'Urinary Tract Infection',
                        'Infection',
                        'UTI',
                        'Antibiotics',
                    ],
                    'alternateIdentifiers' => [],
                    'doiName' => '',
                ],
                'documentation' => [
                    'description' => htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                    'associatedMedia' => [],
                    'isPartOf' => [],
                ],
                'coverage' => [
                    'spatial' => [
                        'United Kingdom,England,West Midlands'
                    ],
                    'typicalAgeRange' => '0-112',
                    'physicalSampleAvailability' => [
                        'NOT AVAILABLE'
                    ],
                    'followup' => 'OTHER',
                    'pathway' => htmlentities(fake()->paragraph(), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                ],
                'provenance' => [
                    'origin' => [
                        'purpose' => [
                            'CARE'
                        ],
                        'source' => [
                            'EPR'
                        ],
                        'collectionSituation' => [
                            'ACCIDENT AND EMERGENCY',
                            'OUTPATIENTS',
                            'IN-PATIENTS',
                        ]
                    ],
                    'temporal' => [
                        'accrualPeriodicity' => 'QUARTERLY',
                        'distributionReleaseDate' => '20/01/2022',
                        'startDate' => '12/01/2000',
                        'endDate' => '01/01/2022',
                        'timeLag' => 'OTHER',
                    ],
                ],
                'accessibility' => [
                    'usage' => [
                        'dataUseLimitation' => [
                            'RESEARCH USE ONLY'
                        ],
                        'dataUseRequirements' => [
                            'PROJECT SPECIFIC RESTRICTIONS',
                        ],
                        'resourceCreator' => [
                            htmlentities(fake()->paragraph(), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                        ],
                        'investigations' => [],
                        'isReferencedBy' => [],
                    ],
                    'access' => [
                        "accessRights" => [
                            "https://www.pioneerdatahub.co.uk/data/data-request-process/"
                        ],
                        "accessService" => htmlentities(fake()->paragraph(), ENT_QUOTES | ENT_IGNORE, "UTF-8"),
                        "accessRequestCost" => [
                            "www.pioneerdatahub.co.uk/data/data-services-costs/"
                        ],
                        "deliveryLeadTime" => "1-2 MONTHS",
                        "jurisdiction" => [
                            "GB-ENG"
                        ],
                        "dataProcessor" => "NOT APPLICABLE",
                        "dataController" => "University Hospitals Birmingham NHS Foundation Trust"
                    ],
                    'formatAndStandards' => [
                        'vocabularyEncodingScheme' => [
                            'SNOMED CT',
                            'OPCS4',
                            'ICD10',
                        ],
                        'conformsTo' => [
                            'LOCAL',
                        ],
                        'language' => [
                            'en',
                        ],
                        'format' => [
                            'SQL',
                        ]
                    ],
                ],
                'enrichmentAndLinkage' => [
                    'qualifiedRelation' => [],
                    'derivation' => [
                        'Not Available',
                    ],
                    'tools' => [],
                ],
                'observations' => [
                    [
                        'measuredProperty' => 'Count',
                        'observedNode' => 'EVENTS',
                        'measuredValue' => '91568',
                        'disambiguatingDescription' => '91,568 spells with patients with diabetes between 12-01-2000 and 01-01-2022',
                        'observationDate' => '02/01/2022',
                    ]
                ]
            ]
        ];
    }
}
