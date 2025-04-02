<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\DataAccessTemplate;
use App\Jobs\SendEmailJob;
use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;
use App\Http\Enums\TeamMemberOf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Illuminate\Support\Facades\Queue;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Database\Seeders\DataAccessTemplateSeeder;
use Database\Seeders\DataAccessApplicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DataAccessApplicationTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected $metadata;

    public function setUp(): void
    {
        $this->commonSetUp();

        Team::flushEventListeners();

        Queue::fake([
            LinkageExtraction::class,
            TermExtraction::class,
            SendEmailJob::class,
        ]);

        $this->seed([
            MinimalUserSeeder::class,
            QuestionBankSeeder::class,
            DataAccessApplicationSeeder::class,
            DataAccessTemplateSeeder::class,
            SpatialCoverageSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            EmailTemplateSeeder::class,
        ]);

        $this->metadata = $this->getMetadata();
    }

    /**
     * List all dar applications.
     *
     * @return void
     */
    public function test_the_application_can_list_dar_applications()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'SUBMITTED',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $response = $this->get('api/v1/teams/' . $teamId . '/dar/applications', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'applicant_id',
                        'user',
                        'datasets',
                        'teams',
                        'project_title',
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

        $response = $this->get('api/v1/users/1/dar/applications', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'applicant_id',
                        'user',
                        'datasets',
                        'teams',
                        'project_title',
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
     * Count all dar applications.
     *
     * @return void
     */
    public function test_the_application_can_count_dar_applications()
    {
        $entityIds1 = $this->createDatasetForDar();
        $datasetId1 = $entityIds1['datasetId'];
        $teamId1 = $entityIds1['teamId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test draft DAR',
                'dataset_ids' => [$datasetId1]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $entityIds2 = $this->createDatasetForDar();
        $datasetId2 = $entityIds2['datasetId'];
        $teamId2 = $entityIds2['teamId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'SUBMITTED',
                'project_title' => 'A test joint DAR',
                'dataset_ids' => [$datasetId1, $datasetId2]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $applicationId2 = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId1 . '/dar/applications/' . $applicationId2,
            [
                'approval_status' => 'APPROVED_COMMENTS',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $response = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId2 . '/dar/applications/' . $applicationId2,
            [
                'approval_status' => 'APPROVED',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $entityIds3 = $this->createDatasetForDar();
        $datasetId3 = $entityIds3['datasetId'];
        $teamId3 = $entityIds3['teamId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'SUBMITTED',
                'project_title' => 'A test joint DAR',
                'dataset_ids' => [$datasetId1, $datasetId3]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $applicationId3 = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId3 . '/dar/applications/' . $applicationId3,
            [
                'approval_status' => 'FEEDBACK',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $response = $this->json(
            'POST',
            'api/v1/teams/' . $teamId3 . '/dar/applications/' . $applicationId3 . '/reviews',
            [
                'comment' => 'A test review comment',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $response = $this->get('api/v1/users/1/dar/applications/count', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson()['data'];
        $this->assertEquals(1, $content['DRAFT']);
        $this->assertEquals(1, $content['SUBMITTED']);
        $this->assertEquals(2, $content['APPROVED']);
        $this->assertEquals(1, $content['info_required']);
        $this->assertEquals(5, $content['ALL'], 5);


        $response = $this->get('api/v1/teams/' . $teamId1 . '/dar/applications/count', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson()['data'];
        $this->assertEquals(1, $content['APPROVED']);
        $this->assertEquals(0, $content['info_required']);
        $this->assertEquals(2, $content['ALL']);

        $response = $this->get('api/v1/teams/' . $teamId2 . '/dar/applications/count', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson()['data'];
        $this->assertEquals(1, $content['ALL']);

        $response = $this->get('api/v1/teams/' . $teamId3 . '/dar/applications/count', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson()['data'];
        $this->assertEquals(1, $content['FEEDBACK']);
        $this->assertEquals(1, $content['info_required']);
        $this->assertEquals(1, $content['ALL']);
    }

    /**
     * Returns a single dar application
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_dar_application()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/teams/' . $teamId . '/dar/applications/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'applicant_id',
                    'project_title',
                    'questions',
                    'teams' => [
                        0 => [
                            'submission_status',
                            'approval_status',
                        ]
                    ],
                    'days_since_submission',
                    'submission_date',
                ],
            ]);

        $response = $this->get('api/v1/users/1/dar/applications/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'applicant_id',
                    'project_title',
                    'questions',
                    'teams' => [
                        0 => [
                            'submission_status',
                            'approval_status',
                        ]
                    ],
                    'days_since_submission',
                    'submission_date',
                ],
            ]);
    }

    /**
     * Test files associated with a dar application can be listed and downloaded
     *
     * @return void
     */
    public function test_the_application_can_list_and_download_dar_application_files()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $applicationId = $content['data'];

        $file = UploadedFile::fake()->create('test_dar_application.pdf');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-application-upload&application_id=' . $applicationId . '&question_id=' . $questionId,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        $response->assertStatus(200);

        // test it can list files
        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/' . $applicationId . '/files',
            [],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'filename',
                        'file_location',
                        'user_id',
                        'status',
                        'entity_id',
                        'question_id',
                        'error',
                    ],
                ]
            ]);

        $fileId = $response->decodeResponseJson()['data'][0]['id'];

        // test downloading a file associated with the dar application
        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/' . $applicationId . '/files/' . $fileId . '/download',
            [],
            $this->header,
        );
        $response->assertStatus(200);

        // test dar manager cannot view and download the file because the application is a draft
        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/files',
            [],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/files/' . $fileId . '/download',
            [],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));

        // test user can delete the file while the application is still a draft
        $response = $this->json(
            'DELETE',
            'api/v1/users/1/dar/applications/' . $applicationId . '/files/' . $fileId,
            [],
            $this->header,
        );
        $response->assertStatus(200);

        // upload a new file
        $file = UploadedFile::fake()->create('test_dar_application.pdf');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-application-upload&application_id=' . $applicationId . '&question_id=' . $questionId,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        $response->assertStatus(200);

        // submit the application
        $response = $this->json(
            'PATCH',
            'api/v1/users/1/dar/applications/' . $applicationId,
            [
                'submission_status' => 'SUBMITTED',
            ],
            $this->header
        );
        $response->assertStatus(200);

        // test that the dar manager can view and download the file now that the application has been submitted
        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/files',
            [],
            $this->header,
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'filename',
                        'file_location',
                        'user_id',
                        'status',
                        'entity_id',
                        'question_id',
                        'error',
                    ],
                ]
            ]);
        $fileId = $response->decodeResponseJson()['data'][0]['id'];

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/files/' . $fileId . '/download',
            [],
            $this->header,
        );
        $response->assertStatus(200);

        // test user cannot delete the file now the application has been submitted
        $response = $this->json(
            'DELETE',
            'api/v1/users/1/dar/applications/' . $applicationId . '/files/' . $fileId,
            [],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
    }

    /**
     * Fails to return a single dar application
     *
     * @return void
     */
    public function test_the_application_fails_to_list_a_single_dar_application()
    {
        $beyondId = DB::table('dar_applications')->max('id') + 1;
        $response = $this->get('api/v1/users/1/dar/applications/' . $beyondId, $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    /**
     * Creates a new dar application
     *
     * @return void
     */
    public function test_the_application_can_create_a_dar_application()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        // Test application created with default values
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'project_title' => 'A test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $content = $response->decodeResponseJson();
        $response = $this->get('api/v1/teams/' . $teamId . '/dar/applications/' . $content['data'], $this->header);

        $this->assertEquals('DRAFT', $response['data']['teams'][0]['submission_status']);
        $this->assertNull($response['data']['teams'][0]['approval_status']);
    }

    /**
     * Creates a new document based dar application
     *
     * @return void
     */
    public function test_the_application_can_create_a_document_based_dar_application()
    {
        $teamId = $this->createTeam();
        // Create template and datasets owned by teams
        $team = Team::where('id', $teamId)->first();
        $metadata = $this->getMetadata();
        $metadata['metadata']['summary']['publisher'] = [
            'name' => $team->name,
            'gatewayId' => $team->id
        ];
        $responseDataset = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => 1,
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseDataset->assertStatus(201);
        $datasetId = $responseDataset['data'];

        // Create document based template for the team
        // upload a new template file
        $file = UploadedFile::fake()->create('test_dar_template.docx');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-template-upload&team_id=' . $teamId,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        $response->assertStatus(200);

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $applicationId = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/' . $applicationId,
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'applicant_id',
                    'project_title',
                    'questions',
                    'teams' => [
                        0 => [
                            'submission_status',
                            'approval_status',
                        ]
                    ],
                    'templates'
                ]
            ]);
    }

    /**
     * Adds answers to a dar application
     *
     * @return void
     */
    public function test_the_application_can_add_answers_to_a_dar_application()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $applicationId = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'PUT',
            'api/v1/users/1/dar/applications/' . $applicationId . '/answers',
            [
                'answers' => [
                    0 => [
                        'question_id' => 1,
                        'answer' => [
                            'value' => 'an answer'
                        ]
                    ],
                    1 => [
                        'question_id' => 2,
                        'answer' => [
                            'value' => 'another answer'
                        ]
                    ],
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        // Test it can retrieve the answers
        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/' . $applicationId . '/answers',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'question_id',
                        'application_id',
                        'answer' => [
                            'value'
                        ],
                        'contributor_id',
                    ]
                ]
            ]);

        // Test it cannot retrieve answers from the wrong endpoint
        $response = $this->json(
            'GET',
            'api/v1/users/2/dar/applications/' . $applicationId . '/answers',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

    }

    /**
     * Tests adding files as answers to a dar application
     *
     * @return void
     */
    public function test_the_application_can_upload_files_as_answers_to_a_dar_application()
    {
        $t1 = $this->createTeam();

        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'options' => [],
                'all_custodians' => true,
                'component' => 'FileUpload',
                'validations' => [],
                'title' => 'Single File Upload Question',
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1,
                'is_child' => 0,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $q1 = $response->decodeResponseJson()['data'];

        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'options' => [],
                'all_custodians' => true,
                'component' => 'FileUploadMultiple',
                'validations' => [],
                'title' => 'Multiple File Upload Question',
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1,
                'is_child' => 0,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $q2 = $response->decodeResponseJson()['data'];

        // Create template and datasets owned by teams
        $team1 = Team::where('id', $t1)->first();

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => $team1->id,
                'published' => true,
                'locked' => false,
                'questions' => [
                    0 => [
                        'id' => $q1,
                        'required' => true,
                        'guidance' => 'Question One Guidance',
                        'order' => 1,
                    ],
                    1 => [
                        'id' => $q2,
                        'required' => true,
                        'guidance' => 'Question Two Guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $metadata1 = $this->getMetadata();
        $metadata1['metadata']['summary']['publisher'] = [
            'name' => $team1->name,
            'gatewayId' => $team1->id
        ];
        $responseDataset1 = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $team1->id,
                'user_id' => 1,
                'metadata' => $metadata1,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseDataset1->assertStatus(201);
        $datasetId1 = $responseDataset1['data'];

        // Create DAR application for that dataset - template will have file upload questions
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId1],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $applicationId = $response->decodeResponseJson()['data'];

        // upload file
        $file = UploadedFile::fake()->create('test_dar_application.pdf');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-application-upload&application_id=' . $applicationId . '&question_id=' . $q1,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        $response->assertStatus(200);

        // get answers
        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/' . $applicationId . '/answers',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    0 => [
                        'answer'
                    ]
                ]
            ]);
        $answer = $response->decodeResponseJson()['data'][0]['answer'];
        $this->assertEquals('test_dar_application.pdf', $answer['value']['filename']);

        // upload multiple files
        $file = UploadedFile::fake()->create('test_file_one.pdf');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-application-upload&application_id=' . $applicationId . '&question_id=' . $q2,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        $response->assertStatus(200);

        $file = UploadedFile::fake()->create('test_file_two.pdf');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-application-upload&application_id=' . $applicationId . '&question_id=' . $q2,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        $response->assertStatus(200);

        // get answers
        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/' . $applicationId . '/answers',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    0 => [
                        'answer'
                    ],
                    1 => [
                        'answer'
                    ]
                ]
            ]);
        $answer = $response->decodeResponseJson()['data'][1]['answer'];
        $this->assertEquals(2, count($answer['value']));
        $this->assertContains('test_file_one.pdf', array_column($answer['value'], 'filename'));
        $this->assertContains('test_file_two.pdf', array_column($answer['value'], 'filename'));
    }

    /**
     * Adds question specific reviews to a dar application
     *
     * @return void
     */
    public function test_the_application_can_add_question_specific_reviews_to_a_dar_application()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $applicationId = $response->decodeResponseJson()['data'];

        // Add a review to the dar
        $response = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/questions/' . $questionId . '/reviews',
            [
                'comment' => 'A test review comment',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $reviewId = $response->decodeResponseJson()['data'];

        // Retrieve review
        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'application_id',
                        'question_id',
                        'resolved',
                        'comments' => [
                            0 => [
                                'id',
                                'created_at',
                                'updated_at',
                                'deleted_at',
                                'team_id',
                                'user_id',
                                'comment',
                            ]
                        ],
                        'files',
                    ]
                ]
            ]);
        $content = $response->decodeResponseJson()['data'];

        $this->assertEquals(1, count($content[0]['comments']));
        $this->assertEquals('A test review comment', $content[0]['comments'][0]['comment']);

        // User adds another comment to the review
        $response = $this->json(
            'PUT',
            'api/v1/users/1/dar/applications/' . $applicationId . '/questions/' . $questionId . '/reviews/' . $reviewId,
            [
                'comment' => 'Another test review comment',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson()['data'];

        $this->assertEquals(2, count($content[0]['comments']));
        $this->assertEquals('Another test review comment', $content[0]['comments'][1]['comment']);

        // Delete review
        $response = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/questions/' . $questionId . '/reviews/' . $reviewId,
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

    }

    /**
     * Adds general reviews to a dar application
     *
     * @return void
     */
    public function test_the_application_can_add_reviews_to_a_dar_application()
    {
        // Create user with dar.manager role
        $responseCreateUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'XXXXXXXXXX',
                'lastname' => 'XXXXXXXXXX',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseCreateUser->assertStatus(201);
        $uniqueUserId = $responseCreateUser->decodeResponseJson()['data'];

        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        // assign dar.manager role to user
        $url = '/api/v1/teams/' . $teamId . '/users';
        $responseUserRole = $this->json(
            'POST',
            $url,
            [
                'userId' => $uniqueUserId,
                'roles' => [
                    'custodian.dar.manager'
                ]
            ],
            $this->header
        );
        $responseUserRole->assertStatus(201);

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $applicationId = $response->decodeResponseJson()['data'];

        // Clear the fake queue
        Queue::fake();

        // Add a review to the dar
        $response = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews',
            [
                'comment' => 'A test review comment',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $reviewId = $response->decodeResponseJson()['data'];
        Queue::assertPushed(SendEmailJob::class, 1);

        // Retrieve review
        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'application_id',
                        'question_id',
                        'resolved',
                        'comments' => [
                            0 => [
                                'id',
                                'created_at',
                                'updated_at',
                                'deleted_at',
                                'team_id',
                                'user_id',
                                'comment',
                                'team_name',
                                'user_name',
                            ]
                        ],
                        'files',
                    ]
                ]
            ]);
        $content = $response->decodeResponseJson()['data'];

        $this->assertEquals(1, count($content[0]['comments']));
        $this->assertEquals('A test review comment', $content[0]['comments'][0]['comment']);

        Queue::fake();

        // User adds another comment to the review
        $response = $this->json(
            'PUT',
            'api/v1/users/1/dar/applications/' . $applicationId . '/reviews/' . $reviewId,
            [
                'comment' => 'Another test review comment',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        Queue::assertPushed(SendEmailJob::class, 1);

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson()['data'];

        $this->assertEquals(2, count($content[0]['comments']));
        $this->assertEquals('Another test review comment', $content[0]['comments'][1]['comment']);

        // Delete review
        $response = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews/' . $reviewId,
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

    }

    /**
     * Adds files to a review of a dar application
     *
     * @return void
     */
    public function test_the_application_can_add_review_files_to_a_dar_application()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED',
                'dataset_ids' => [$datasetId]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $applicationId = $response->decodeResponseJson()['data'];

        // Add a review to the dar
        $response = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews',
            [
                'comment' => 'A test review comment',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $reviewId = $response->decodeResponseJson()['data'];

        // Add file to the review
        $file = UploadedFile::fake()->create('test_dar_review.docx');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-review-upload&review_id=' . $reviewId,
            [
                'file' => $file
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'multipart/form-data',
                'Authorization' => $this->header['Authorization']
            ]
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $uploadId = $response->decodeResponseJson()['data']['id'];

        // Team can download
        $response = $this->get(
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews/' . $reviewId . '/download/' . $uploadId,
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));


        // user can download
        $response = $this->get(
            'api/v1/users/1/dar/applications/' . $applicationId . '/reviews/' . $reviewId . '/download/' . $uploadId,
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // team can delete
        $response = $this->json(
            'DELETE',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/reviews/' . $reviewId . '/files/' . $uploadId,
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }

    /**
     * Lists dar applications by team
     *
     * @return void
     */
    public function test_the_application_can_list_dar_applications_by_team()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        // Create first DAR application for that dataset
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'First test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $applicationIdOne = $response->decodeResponseJson()['data'];

        // Create second DAR application for that dataset
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'SUBMITTED',
                'project_title' => 'Second test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $applicationIdTwo = $response->decodeResponseJson()['data'];

        // Update approval status of application two
        $response = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationIdTwo,
            [
                'approval_status' => 'APPROVED_COMMENTS',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // Add a review to second DAR application
        $response = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationIdTwo . '/questions/' . $questionId . '/reviews',
            [
                'comment' => 'A test review comment',
                'team_id' => $teamId
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'applicant_id',
                        'project_title',
                        'days_since_submission',
                        'primary_applicant' => [
                            'name',
                            'organisation',
                        ],
                        'user' => [
                            'id',
                            'name',
                            'organisation'
                        ],
                        'datasets' => [
                            0 => [
                                'dataset_id',
                                'dataset_title',
                                'custodian' => [
                                    'id',
                                    'name'
                                ]
                            ]
                        ],
                        'teams' => [
                            0 => [
                                'submission_status',
                                'approval_status',
                            ]
                        ]
                    ]
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

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications?sort=project_title:desc',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals($applicationIdTwo, $content['data'][0]['id']);

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications?submission_status=SUBMITTED',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals(1, count($content['data']));

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications?approval_status=APPROVED_COMMENTS',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals(1, count($content['data']));

        // action required = false should return the dar with a review that is
        // waiting for a response from the applicant
        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications?action_required=false',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals($applicationIdTwo, $content['data'][0]['id']);
        $this->assertEquals(1, count($content['data']));

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/count/submission_status',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'SUBMITTED',
                    'DRAFT',
                ]
            ]);

        $response = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/count/action_required',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'action_required',
                    'info_required',
                ]
            ]);
    }

    /**
     * Lists dar applications by user
     *
     * @return void
     */
    public function test_the_application_can_list_dar_applications_by_user()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        // Create first DAR application for that dataset
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'First test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $applicationIdOne = $response->decodeResponseJson()['data'];

        // Create second DAR application for that dataset
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'SUBMITTED',
                'project_title' => 'Second test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $applicationIdTwo = $response->decodeResponseJson()['data'];

        // Update approval status of application two
        $response = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationIdTwo,
            [
                'approval_status' => 'APPROVED_COMMENTS',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // Add a review to second DAR application
        $response = $this->json(
            'POST',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationIdTwo . '/questions/' . $questionId . '/reviews',
            [
                'comment' => 'A test review comment',
                'team_id' => $teamId
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'applicant_id',
                        'project_title',
                        'days_since_submission',
                        'primary_applicant' => [
                            'name',
                            'organisation',
                        ],
                        'user' => [
                            'id',
                            'name',
                            'organisation'
                        ],
                        'datasets' => [
                            0 => [
                                'dataset_id',
                                'dataset_title',
                                'custodian' => [
                                    'id',
                                    'name'
                                ]
                            ]
                        ],
                        'teams' => [
                            0 => [
                                'submission_status',
                                'approval_status',
                            ]
                        ]
                    ]
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

        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications?sort=project_title:desc',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals($applicationIdTwo, $content['data'][0]['id']);

        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications?submission_status=SUBMITTED',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals(1, count($content['data']));

        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications?approval_status=APPROVED_COMMENTS',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals(1, count($content['data']));

        // action required = false should return the dar with a review that is
        // waiting for a response from the applicant
        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications?action_required=false',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $content = $response->decodeResponseJson();
        $this->assertEquals($applicationIdTwo, $content['data'][0]['id']);
        $this->assertEquals(1, count($content['data']));

        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/count/submission_status',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'SUBMITTED',
                    'DRAFT',
                ]
            ]);

        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications/count/action_required',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'action_required',
                    'info_required',
                ]
            ]);
    }

    /**
     * Extract primary application and org information
     *
     * @return void
     */
    public function test_the_application_can_extract_primary_applicant_info()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        // Get questions ids for primary applicant name and org
        $nameQuestion = 1;
        $orgQuestion = 6;

        // Get the team's DAR template
        $response = $this->get('api/v1/teams/' . $teamId . '/dar/templates/', $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $templateId = $response->decodeResponseJson()['data'][0]['id'];

        // Add those questions to the team's DAR template
        $response = $this->json(
            'PATCH',
            'api/v1/dar/templates/' . $templateId . '?section_id=2',
            [
                'questions' => [
                    0 => [
                        'id' => $nameQuestion,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ],
                    1 => [
                        'id' => $orgQuestion,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 3,
                    ]
                ]
            ],
            $this->header
        );

        $template = DataAccessTemplate::where('id', $templateId)->with('questions')->first();

        // Create DAR application for that dataset
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'Test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $applicationId = $response->decodeResponseJson()['data'];

        // Add answers to the primary applicant name and org questions
        $response = $this->json(
            'PUT',
            'api/v1/users/1/dar/applications/' . $applicationId . '/answers',
            [
                'answers' => [
                    0 => [
                        'question_id' => $nameQuestion,
                        'answer' => 'Andrea Test'
                    ],
                    1 => [
                        'question_id' => $orgQuestion,
                        'answer' => 'Test University'
                    ],
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $response = $this->get('api/v1/users/1/dar/applications/' . $applicationId . '/answers', $this->header);

        // Call the dashboard endpoint
        // Check the values of primary applicant name and org match answers
        $response = $this->json(
            'GET',
            'api/v1/users/1/dar/applications',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'id',
                        'primary_applicant' => [
                            'name',
                            'organisation',
                        ],
                    ]
                ],
            ]);

        $content = $response->decodeResponseJson();

        $this->assertEquals('Andrea Test', $content['data'][0]['primary_applicant']['name']);
        $this->assertEquals('Test University', $content['data'][0]['primary_applicant']['organisation']);
    }

    /**
     * Creates a new dar application with merged template
     *
     * @return void
     */
    public function test_the_application_can_create_a_dar_application_with_template_merging()
    {
        // Create templates for two teams
        $t1 = $this->createTeam();
        $t2 = $this->createTeam();

        $q1 = $this->createQuestion('Test Question One');
        $q2 = $this->createQuestion('Test Question Two');
        $q3 = $this->createQuestion('Test Question Three');

        // Create template and datasets owned by teams
        $team1 = Team::where('id', $t1)->first();

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => $team1->id,
                'published' => true,
                'locked' => false,
                'questions' => [
                    0 => [
                        'id' => $q1,
                        'required' => true,
                        'guidance' => 'Question One Guidance',
                        'order' => 1,
                    ],
                    1 => [
                        'id' => $q2,
                        'required' => true,
                        'guidance' => 'Question Two Guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $metadata1 = $this->getMetadata();
        $metadata1['metadata']['summary']['publisher'] = [
            'name' => $team1->name,
            'gatewayId' => $team1->id
        ];
        $responseDataset1 = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $team1->id,
                'user_id' => 1,
                'metadata' => $metadata1,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseDataset1->assertStatus(201);
        $datasetId1 = $responseDataset1['data'];

        $team2 = Team::where('id', $t2)->first();

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => $team2->id,
                'published' => true,
                'locked' => false,
                'questions' => [
                    0 => [
                        'id' => $q2,
                        'required' => true,
                        'guidance' => 'Question Two Template Two Guidance',
                        'order' => 1,
                    ],
                    1 => [
                        'id' => $q3,
                        'required' => true,
                        'guidance' => 'Question Three Guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $metadata2 = $this->metadata;
        $metadata2['metadata']['summary']['publisher'] = [
            'name' => $team2->name,
            'gatewayId' => $team2->pid
        ];
        $responseDataset2 = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $team2->id,
                'user_id' => 1,
                'metadata' => $metadata2,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseDataset2->assertStatus(201);
        $datasetId2 = $responseDataset2['data'];

        // Create DAR application for those datasets
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [$datasetId1, $datasetId2],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        // Test questions are merged
        $applicationId = $response->decodeResponseJson()['data'];
        $response = $this->get('api/v1/users/1/dar/applications/' . $applicationId, $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'applicant_id',
                    'project_title',
                    'teams' => [
                        0 => [
                            'submission_status',
                            'approval_status',
                        ]
                    ],
                    'questions' => [
                        0 => [
                            'created_at',
                            'updated_at',
                            'deleted_at',
                            'version',
                            'default',
                            'required',
                            'section_id',
                            'user_id',
                            'locked',
                            'archived',
                            'archived_date',
                            'force_required',
                            'allow_guidance_override',
                            'is_child',
                            'question_type',
                            'title',
                            'guidance',
                            'options',
                            'component',
                            'validations',
                            'version_id',
                            'application_id',
                            'order',
                            'teams',
                            'template_teams',
                        ]
                    ],
                ],
            ]);

        $questions = $response->decodeResponseJson()['data']['questions'];

        $allTeams = array_column($questions, 'template_teams');

        $this->assertContains($team1->name, $allTeams);
        $this->assertContains($team2->name, $allTeams);
        $this->assertContains($team1->name . ',' . $team2->name, $allTeams);

        $allGuidance = implode('\n', array_column($questions, 'guidance'));

        $this->assertStringContainsString($team1->name, $allGuidance);
        $this->assertStringContainsString($team2->name, $allGuidance);
        $this->assertStringContainsString('Question Two Guidance', $allGuidance);
        $this->assertStringContainsString('Question Two Template Two Guidance', $allGuidance);
    }

    /**
     * Fails to create a new application
     *
     * @return void
     */
    public function test_the_application_fails_to_create_a_dar_application()
    {
        // Attempt to create application with incorrect values
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'INVALID',
                'project_title' => 'A test DAR',
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
     * Tests that a dar application record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_dar_application()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        // Create first DAR application for that dataset
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'Test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $applicationId = $content['data'];

        $response = $this->json(
            'PUT',
            'api/v1/users/1/dar/applications/' . $applicationId,
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'answers' => [
                    0 => [
                        'question_id' => 1,
                        'answer' => [
                            'value' => 'an answer'
                        ]
                    ],
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['teams'][0]['submission_status'], 'DRAFT');
    }

    /**
     * Tests that a dar application record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_dar_application()
    {
        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $applicationId = $content['data'];

        $response = $this->json(
            'PATCH',
            'api/v1/users/1/dar/applications/' . $applicationId,
            [
                'submission_status' => 'DRAFT',
                'answers' => [
                    0 => [
                        'question_id' => 1,
                        'answer' => [
                            'value' => 'an answer'
                        ]
                    ],
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['teams'][0]['submission_status'], 'DRAFT');
        $this->assertNull($content['data']['teams'][0]['approval_status']);

        $responseStatus = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/status',
            [],
            $this->header,
        );
        $responseStatus->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $statusCountInit = count($responseStatus->decodeResponseJson()['data']);

        $response = $this->json(
            'PATCH',
            'api/v1/users/1/dar/applications/' . $applicationId,
            [
                'submission_status' => 'SUBMITTED',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['teams'][0]['submission_status'], 'SUBMITTED');

        // Clear test queue
        Queue::fake();

        $response = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId,
            [
                'approval_status' => 'APPROVED',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['teams'][0]['approval_status'], 'APPROVED');
        Queue::assertPushed(SendEmailJob::class, 1);

        // Test status history has been updated
        $responseStatus = $this->json(
            'GET',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId . '/status',
            [],
            $this->header,
        );
        $responseStatus->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $statusCountNew = count($responseStatus->decodeResponseJson()['data']);

        // Check for 2 new status entries - submission and approval
        $this->assertEquals($statusCountNew, $statusCountInit + 2);

        // Test team can push application back to DRAFT and null approval status
        $response = $this->json(
            'PATCH',
            'api/v1/teams/' . $teamId . '/dar/applications/' . $applicationId,
            [
                'submission_status' => 'DRAFT',
                'approval_status' => null,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['teams'][0]['submission_status'], 'DRAFT');
        $this->assertEquals($content['data']['teams'][0]['approval_status'], null);
    }

    /**
     * Tests that a dar application can be submitted and notifications sent
     *
     * @return void
     */
    public function test_the_application_can_submit_a_dar_application()
    {
        // Create user with dar.manager role
        $responseCreateUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'XXXXXXXXXX',
                'lastname' => 'XXXXXXXXXX',
                'email' => 'just.test.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'contact_feedback' => 1,
                'contact_news' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/12345678",
                'mongo_id' => 1234567,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header
        );
        $responseCreateUser->assertStatus(201);
        $uniqueUserId = $responseCreateUser->decodeResponseJson()['data'];

        $entityIds = $this->createDatasetForDar();
        $datasetId = $entityIds['datasetId'];
        $teamId = $entityIds['teamId'];
        $questionId = $entityIds['questionId'];

        // assign dar.manager role to user
        $url = '/api/v1/teams/' . $teamId . '/users';
        $responseUserRole = $this->json(
            'POST',
            $url,
            [
                'userId' => $uniqueUserId,
                'roles' => [
                    'custodian.dar.manager'
                ]
            ],
            $this->header
        );
        $responseUserRole->assertStatus(201);

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'dataset_ids' => [$datasetId],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $applicationId = $content['data'];

        // Clear the fake queue
        Queue::fake();

        $response = $this->json(
            'PATCH',
            'api/v1/users/1/dar/applications/' . $applicationId,
            [
                'submission_status' => 'SUBMITTED',
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        // Assert email job called twice - once for researcher and once for dar.manager
        Queue::assertPushed(SendEmailJob::class, 2);
    }

    /**
     * Tests that a dar application record cannot be updated when status is submitted
     *
     * @return void
     */
    public function test_the_application_fails_to_update_a_dar_application()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'dataset_ids' => [1,2],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $applicationId = $content['data'];

        $response = $this->json(
            'PUT',
            'api/v1/dar/applications/' . $applicationId,
            [
                'applicant_id' => 1,
                'submission_status' => 'SUBMITTED',
                'project_title' => 'A test DAR',
                'answers' => [
                    0 => [
                        'question_id' => 1,
                        'answer' => [
                            'value' => 'an answer'
                        ]
                    ],
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));

        $response = $this->json(
            'PATCH',
            'api/v1/dar/applications/' . $applicationId,
            [
                'submission_status' => 'SUBMITTED',
                'answers' => [
                    0 => [
                        'question_id' => 1,
                        'answer' => [
                            'value' => 'an answer'
                        ]
                    ],
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));
    }

    /**
     * Tests the user can withdraw a dar application
     *
     * @return void
     */
    public function test_it_can_withdraw_a_dar_application()
    {

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'dataset_ids' => [1,2],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'PATCH',
            'api/v1/users/1/dar/applications/' . $content['data'],
            [
                'approval_status' => 'WITHDRAWN',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // But a user cannot approve their own application
        $response = $this->json(
            'PATCH',
            'api/v1/users/1/dar/applications/' . $content['data'],
            [
                'approval_status' => 'APPROVED',
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    /**
     * Tests it can delete a dar application
     *
     * @return void
     */
    public function test_it_can_delete_a_dar_application()
    {

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'dataset_ids' => [1,2],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'DELETE',
            'api/v1/dar/applications/' . $content['data'],
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }

    /**
     * Tests a user can delete a dar application
     *
     * @return void
     */
    public function test_user_can_delete_a_dar_application()
    {

        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'dataset_ids' => [1,2],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'DELETE',
            'api/v1/users/1/dar/applications/' . $content['data'],
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);

        // Test user cannot delete after submission
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'SUBMITTED',
                'project_title' => 'A test DAR',
                'dataset_ids' => [1,2],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'DELETE',
            'api/v1/users/1/dar/applications/' . $content['data'],
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }

    private function createQuestion(string $title): int
    {
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'options' => [],
                'all_custodians' => true,
                'component' => 'TextArea',
                'validations' => [
                    [
                        'min' => 1,
                        'message' => 'Please enter a value'
                    ]
                ],
                'title' => $title,
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1,
                'is_child' => 0,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $questionId = $response->decodeResponseJson()['data'];

        return $questionId;
    }

    private function createTeam(): int
    {
        // Create team for the user to belong to
        $responseTeam = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => TeamMemberOf::HUB,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'is_question_bank' => 1,
                'users' => [],
                'notifications' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
            ],
            $this->header
        );
        $responseTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $content = $responseTeam->decodeResponseJson();
        $teamId = $content['data'];
        return $teamId;
    }

    private function createDatasetForDar(): array
    {
        $teamId = $this->createTeam();
        $questionId = $this->createQuestion('Test Question One');

        // Create template and datasets owned by teams
        $team = Team::where('id', $teamId)->first();

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => $teamId,
                'published' => true,
                'locked' => false,
                'questions' => [
                    0 => [
                        'id' => $questionId,
                        'required' => true,
                        'guidance' => 'Question One Guidance',
                        'order' => 1,
                    ],
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $metadata = $this->getMetadata();
        $metadata['metadata']['summary']['publisher'] = [
            'name' => $team->name,
            'gatewayId' => $team->id
        ];
        $responseDataset = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => 1,
                'metadata' => $metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseDataset->assertStatus(201);
        $datasetId = $responseDataset['data'];

        return [
            'datasetId' => $datasetId,
            'teamId' => $teamId,
            'questionId' => $questionId,
        ];
    }
}
