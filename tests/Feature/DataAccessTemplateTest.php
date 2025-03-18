<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\DataAccessTemplateSeeder;
use Database\Seeders\QuestionBankSeeder;

use Tests\Traits\MockExternalApis;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DataAccessTemplateTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            QuestionBankSeeder::class,
            DataAccessTemplateSeeder::class,
        ]);
    }

    /**
     * List all dar templates.
     *
     * @return void
     */
    public function test_the_application_can_list_dar_templates()
    {
        $response = $this->get('api/v1/dar/templates', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'user_id',
                        'team_id',
                        'published',
                        'locked',
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
     * Returns a single dar template
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_dar_template()
    {
        // Create a question to associate with the template
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
                'title' => 'Test question',
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

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => true,
                'locked' => false,
                'questions' => [
                    0 => [
                        'id' => $questionId,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/dar/templates/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'user_id',
                    'team_id',
                    'published',
                    'locked',
                    'questions' => [
                        0 => [
                            'template_id',
                            'question_id',
                            'guidance',
                            'required',
                            'order',
                            'latest_version' => [
                                'question_json',
                                'child_versions'
                            ],
                        ]
                    ],
                ],
            ]);
    }

    /**
     * Test listing dar templates by team
     *
     * @return void
     */
    public function test_the_application_can_list_dar_templates_by_team()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => false,
                'locked' => false,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $templateId = $content['data'];

        $response = $this->get('api/v1/teams/' . 1 . '/dar/templates/', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'user_id',
                        'team_id',
                        'published',
                        'locked',
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
        $templates = $response->decodeResponseJson()['data'];

        $this->assertContains($templateId, array_column($templates, 'id'));
    }

    /**
     * Fails to return a single dar template
     *
     * @return void
     */
    public function test_the_application_fails_to_list_a_single_dar_template()
    {
        $beyondId = DB::table('dar_templates')->max('id') + 1;
        $response = $this->get('api/v1/dar/templates/' . $beyondId, $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    /**
     * Creates a new dar template
     *
     * @return void
     */
    public function test_the_application_can_create_a_dar_template()
    {
        // Create a question to associate with the template
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
                'title' => 'Test question',
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

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => true,
                'locked' => true,
                'questions' => [
                    0 => [
                        'id' => $questionId,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $content = $response->decodeResponseJson();
        $response = $this->get('api/v1/dar/templates/' . $content['data'], $this->header);

        $this->assertEquals(true, $response['data']['published']);
        $this->assertEquals(true, $response['data']['locked']);

        $this->assertEquals('Custom guidance', $response['data']['questions'][0]['guidance']);
        $this->assertEquals(2, $response['data']['questions'][0]['order']);

        // Test template created with default values
        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'questions' => [
                    0 => [
                        'id' => $questionId,
                    ]
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $content = $response->decodeResponseJson();
        $response = $this->get('api/v1/dar/templates/' . $content['data'], $this->header);

        $this->assertEquals(false, $response['data']['published']);
        $this->assertEquals(false, $response['data']['locked']);

        $this->assertEquals('Something helpful', $response['data']['questions'][0]['guidance']);
        $this->assertEquals(1, $response['data']['questions'][0]['order']);
    }

    /**
     * Creates a new document based dar template
     *
     * @return void
     */
    public function test_the_application_can_create_a_document_based_dar_template()
    {
        // upload a new template file
        $file = UploadedFile::fake()->create('test_dar_template.docx');
        $response = $this->json(
            'POST',
            'api/v1/files?entity_flag=dar-template-upload&team_id=1',
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
        $uploadId = $response->decodeResponseJson()['data']['id'];

        $response = $this->get('api/v1/files/' . $uploadId, $this->header);
        $templateId = $response->decodeResponseJson()['data']['entity_id'];

        // test the template can be downloaded
        $response = $this->get(
            'api/v1/dar/templates/' . $templateId . '/download',
            $this->header
        );
        $response->assertStatus(200);

        // test the template is listed by team
        $response = $this->get('api/v1/teams/' . 1 . '/dar/templates/', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'user_id',
                        'team_id',
                        'published',
                        'locked',
                        'files',
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
        $templates = $response->decodeResponseJson()['data'];

        $this->assertContains($templateId, array_column($templates, 'id'));
    }

    /**
     * Fails to create a new template
     *
     * @return void
     */
    public function test_the_application_fails_to_create_a_dar_template()
    {
        // Attempt to create template when no team id provided
        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJsonStructure([
                'status',
                'message',
                'errors',
            ]);

        // Attempt to create a template using a custom question from another team
        // Create a question for team 2
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'team_ids' => [2],
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'question_type' => 'CUSTOM',
                'all_custodians' => false,
                'options' => [],
                'component' => 'TextArea',
                'validations' => [
                    [
                        'min' => 1,
                        'message' => 'Please enter a value'
                    ]
                ],
                'title' => 'Test question',
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

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => true,
                'locked' => true,
                'questions' => [
                    0 => [
                        'id' => $questionId,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $content = $response->decodeResponseJson();
        $this->assertStringContainsString('not accessible by this team', $content['message']);
    }

    /**
     * Tests that a dar template record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_dar_template()
    {
        // Create a question to associate with the template
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
                'title' => 'Test question',
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

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => false,
                'locked' => false,
                'questions' => [
                    0 => [
                        'id' => $questionId,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $templateId = $content['data'];

        $response = $this->json(
            'PUT',
            'api/v1/dar/templates/' . $templateId,
            [
                'team_id' => 1,
                'published' => true,
                'locked' => true,
                'questions' => [
                    0 => [
                        'id' => $questionId,
                        'required' => true,
                        'guidance' => 'Custom guidance updated',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $response = $this->get('api/v1/dar/templates/' . $templateId, $this->header);
        $content = $response->decodeResponseJson();

        $this->assertEquals(true, $content['data']['published']);
        $this->assertEquals(true, $content['data']['locked']);
        $this->assertEquals('Custom guidance updated', $content['data']['questions'][0]['guidance']);
    }

    /**
     * Tests that a dar template record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_dar_template()
    {
        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => false,
                'locked' => false,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();
        $templateId = $content['data'];

        $response = $this->json(
            'PATCH',
            'api/v1/dar/templates/' . $templateId,
            [
                'locked' => true,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(false, $content['data']['published']);
        $this->assertEquals(true, $content['data']['locked']);
    }

    /**
     * Edits a new dar template by section
     *
     * @return void
     */
    public function test_the_application_can_edit_a_dar_template_by_section()
    {
        $q1 = $this->createQuestion(1);
        $q2 = $this->createQuestion(2);
        $q3 = $this->createQuestion(2);

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => true,
                'locked' => false,
                'questions' => [
                    0 => [
                        'id' => $q1,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ],
                    1 => [
                        'id' => $q2,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data'
            ]);

        $templateId = $response->decodeResponseJson()['data'];

        // Edit template section 2 - replace q2 with q3
        $response = $this->json(
            'PATCH',
            'api/v1/dar/templates/' . $templateId . '?section_id=2',
            [
                'questions' => [
                    0 => [
                        'id' => $q3,
                        'required' => true,
                        'guidance' => 'Custom guidance',
                        'order' => 2,
                    ]
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $response = $this->get('api/v1/dar/templates/' . $templateId, $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $qIds = array_column(
            $response->decodeResponseJson()['data']['questions'],
            'question_id'
        );

        $this->assertContains($q1, $qIds);
        $this->assertContains($q3, $qIds);
        $this->assertNotContains($q2, $qIds);
    }

    /**
     * Tests it can delete a dar template
     *
     * @return void
     */
    public function test_it_can_delete_a_dar_template()
    {

        $response = $this->json(
            'POST',
            'api/v1/dar/templates',
            [
                'team_id' => 1,
                'published' => false,
                'locked' => false,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $content = $response->decodeResponseJson();

        $response = $this->json(
            'DELETE',
            'api/v1/dar/templates/' . $content['data'],
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }

    private function createQuestion(int $sectionId): int
    {
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => $sectionId,
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
                'title' => 'Test question section two',
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1,
                'is_child' => 0,
            ],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $qId = $response->decodeResponseJson()['data'];

        return $qId;
    }
}
