<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use App\Models\Dataset;
use App\Models\Team;
use App\Http\Enums\TeamMemberOf;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\DataAccessApplicationSeeder;
use Database\Seeders\DataAccessTemplateSeeder;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\SpatialCoverageSeeder;

use Tests\Traits\MockExternalApis;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->seed([
            MinimalUserSeeder::class,
            QuestionBankSeeder::class,
            DataAccessApplicationSeeder::class,
            DataAccessTemplateSeeder::class,
            SpatialCoverageSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
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
                    'approval_status',
                    'questions',
                ],
            ]);
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
        $questions = $response->decodeResponseJson()['data']['questions'];

        $allTeams = array_column($questions, 'teams');

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
                'dataset_ids' => [1,2],
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'PUT',
            'api/v1/dar/applications/' . $content['data'],
            [
                'applicant_id' => 1,
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
        $this->assertEquals($content['data']['submission_status'], 'SUBMITTED');
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
        $this->assertEquals($content['data']['submission_status'], 'SUBMITTED');
        $this->assertNull($content['data']['approval_status']);

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
                'field' => [
                    'options' => [],
                    'component' => 'TextArea',
                    'validations' => [
                        [
                            'min' => 1,
                            'message' => 'Please enter a value'
                        ]
                    ]
                ],
                'title' => $title,
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1
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
