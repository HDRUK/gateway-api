<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use App\Jobs\LinkageExtraction;
use App\Jobs\SendEmailJob;
use App\Jobs\TermExtraction;
use App\Models\Dataset;
use App\Models\QuestionBank;
use App\Models\Team;
use App\Http\Enums\TeamMemberOf;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\DataAccessApplicationSeeder;
use Database\Seeders\DataAccessTemplateSeeder;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\SpatialCoverageSeeder;

use Tests\Traits\MockExternalApis;

use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

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
        $response = $this->get('api/v1/dar/applications', $this->header);

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
                        'submission_status',
                        'project_title',
                        'approval_status',
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
     * Returns a single dar application
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_dar_application()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [1,2]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/dar/applications/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'applicant_id',
                    'submission_status',
                    'project_title',
                    'approval_status',
                    'questions',
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
                    'submission_status',
                    'project_title',
                    'approval_status',
                    'questions',
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
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [1,2]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $applicationId = $content['data'];
        $questionId = QuestionBank::all()->random()->id;

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
            'api/v1/dar/applications/' . $applicationId . '/files',
            [],
            $this->header,
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));

        $response = $this->json(
            'GET',
            'api/v1/dar/applications/' . $applicationId . '/files/' . $fileId . '/download',
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
            'api/v1/dar/applications/' . $applicationId . '/files',
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
            'api/v1/dar/applications/' . $applicationId . '/files/' . $fileId . '/download',
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
        $response = $this->get('api/v1/dar/applications/' . $beyondId, $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    /**
     * Creates a new dar application
     *
     * @return void
     */
    public function test_the_application_can_create_a_dar_application()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [1,2],
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
                'dataset_ids' => [1,2],
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $content = $response->decodeResponseJson();
        $response = $this->get('api/v1/dar/applications/' . $content['data'], $this->header);

        $this->assertEquals('DRAFT', $response['data']['submission_status']);
        $this->assertNull($response['data']['approval_status']);
    }

    /**
     * Adds answers to a dar application
     *
     * @return void
     */
    public function test_the_application_can_add_answers_to_a_dar_application()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
                'dataset_ids' => [1,2],
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
     * Adds reviews to a dar application
     *
     * @return void
     */
    public function test_the_application_can_add_reviews_to_a_dar_application()
    {
        // Create team with a dataset and a dar template containing known question
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

        // Create DAR application for that dataset
        $response = $this->json(
            'POST',
            'api/v1/dar/applications',
            [
                'applicant_id' => 1,
                'submission_status' => 'DRAFT',
                'project_title' => 'A test DAR',
                'approval_status' => 'APPROVED_COMMENTS',
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

        // Add a review to the dar
        $response = $this->json(
            'POST',
            'api/v1/dar/applications/' . $applicationId . '/questions/' . $questionId . '/reviews',
            [
                'review_comment' => 'A test review comment'
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
            'api/v1/dar/applications/' . $applicationId . '/reviews',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $content = $response->decodeResponseJson()['data'];

        $this->assertEquals(1, count($content));

        // Update review
        $response = $this->json(
            'PUT',
            'api/v1/dar/applications/' . $applicationId . '/questions/' . $questionId . '/reviews/' . $reviewId,
            [
                'review_comment' => 'A test review comment - updated'
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);
        $content = $response->decodeResponseJson()['data'];

        $this->assertEquals('A test review comment - updated', $content['review_comment']);

        // Delete review
        $response = $this->json(
            'DELETE',
            'api/v1/dar/applications/' . $applicationId . '/questions/' . $questionId . '/reviews/' . $reviewId,
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

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
        $response = $this->get('api/v1/dar/applications/' . $applicationId, $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'applicant_id',
                    'submission_status',
                    'project_title',
                    'approval_status',
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
        $this->assertEquals($content['data']['submission_status'], 'DRAFT');
    }

    /**
     * Tests that a dar application record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_dar_application()
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
            'PATCH',
            'api/v1/dar/applications/' . $applicationId,
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
        $this->assertEquals($content['data']['submission_status'], 'DRAFT');
        $this->assertNull($content['data']['approval_status']);

        $responseStatus = $this->json(
            'GET',
            'api/v1/dar/applications/' . $applicationId . '/status',
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
            'api/v1/dar/applications/' . $applicationId,
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
        $this->assertEquals($content['data']['approval_status'], 'APPROVED');
        Queue::assertPushed(SendEmailJob::class, 1);

        // Test status history has been updated
        $responseStatus = $this->json(
            'GET',
            'api/v1/dar/applications/' . $applicationId . '/status',
            [],
            $this->header,
        );
        $responseStatus->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);
        $statusCountNew = count($responseStatus->decodeResponseJson()['data']);

        $this->assertEquals($statusCountNew, $statusCountInit + 1);
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
                'users' => [$uniqueUserId],
                'notifications' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
            ],
            $this->header
        );
        $responseTeam->assertStatus(200);

        $content = $responseTeam->decodeResponseJson();
        $teamId = $content['data'];

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

        // Create dataset belonging to the team
        $responseCreateDataset = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => $uniqueUserId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $datasetId = $responseCreateDataset['data'];

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
        $responseTeam->assertStatus(200);

        $content = $responseTeam->decodeResponseJson();
        $teamId = $content['data'];
        return $teamId;
    }
}
