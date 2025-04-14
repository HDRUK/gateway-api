<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use App\Models\QuestionBank;
use App\Models\QuestionBankVersion;
use App\Models\QuestionHasTeam;
use Tests\TestCase;
use Database\Seeders\TeamSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\QuestionBankSeeder;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\DB;

class QuestionBankTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            TeamSeeder::class,
            MinimalUserSeeder::class,
            QuestionBankSeeder::class,
        ]);
    }

    /**
     * List all questions.
     *
     * @return void
     */
    public function test_the_application_can_list_questions()
    {
        $response = $this->get('api/v1/questions', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'question_id',
                        'section_id',
                        'user_id',
                        'locked',
                        'archived',
                        'archived_date',
                        'force_required',
                        'allow_guidance_override',
                        'question_type',
                        'is_child',
                        'version',
                        'default',
                        'required',
                        'title',
                        'guidance',
                        'options',
                        'component',
                        'validations',
                        'version_id',
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

        $response = $this->get('api/v1/questions?section_id=3&is_child=0', $this->header);

        $content = $response->decodeResponseJson();

        foreach ($content['data'] as $question) {
            $this->assertEquals(3, $question['section_id']);
            $this->assertEquals(0, $question['is_child']);
        }
    }

    /**
     * List all standard questions.
     *
     * @return void
     */
    public function test_the_application_can_list_standard_questions()
    {
        // Create a standard question to list
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'question_type' => 'STANDARD',
                'all_custodians' => true,
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $response = $this->get('api/v1/questions/standard', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'question_id',
                        'section_id',
                        'user_id',
                        'locked',
                        'archived',
                        'archived_date',
                        'force_required',
                        'allow_guidance_override',
                        'question_type',
                        'is_child',
                        'version',
                        'default',
                        'required',
                        'title',
                        'guidance',
                        'options',
                        'component',
                        'validations',
                        'version_id',
                        'all_custodians',
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
     * List all custom questions.
     *
     * @return void
     */
    public function test_the_application_can_list_custom_questions()
    {
        // Create a custom question to list
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'team_ids' => [1, 2],
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $response = $this->get('api/v1/questions/custom', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'question_id',
                        'section_id',
                        'user_id',
                        'locked',
                        'archived',
                        'archived_date',
                        'force_required',
                        'allow_guidance_override',
                        'question_type',
                        'is_child',
                        'version',
                        'default',
                        'required',
                        'title',
                        'guidance',
                        'options',
                        'component',
                        'validations',
                        'version_id',
                        'all_custodians',
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
     * List all archived questions.
     *
     * @return void
     */
    public function test_the_application_can_list_archived_questions()
    {
        // Create an archived question to list
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'archived' => 1,
                'options' => [],
                'component' => 'TextArea',
                'validations' => [
                    'min' => 1,
                    'message' => 'Please enter a value'
                ],
                'title' => 'Test question',
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1,
                'is_child' => 0,
                'all_custodians' => true,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $response = $this->get('api/v1/questions/archived', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    0 => [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'question_id',
                        'section_id',
                        'user_id',
                        'locked',
                        'archived',
                        'archived_date',
                        'force_required',
                        'allow_guidance_override',
                        'question_type',
                        'is_child',
                        'version',
                        'default',
                        'required',
                        'title',
                        'guidance',
                        'options',
                        'component',
                        'validations',
                        'version_id',
                        'all_custodians',
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
     * List all questions from a section.
     *
     * @return void
     */
    public function test_the_application_can_list_questions_by_section()
    {
        // Create a question in section 1 to list
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'team_ids' => [1],
                'question_type' => 'CUSTOM',
                'all_custodians' => false,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'options' => [],
                'component' => 'TextArea',
                'validations' => [
                    'min' => 1,
                    'message' => 'Please enter a value'
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $questionId = $response->decodeResponseJson()['data'];

        $response = $this->get('api/v1/teams/1/questions/section/1', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    0 => [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'question_id',
                        'section_id',
                        'user_id',
                        'locked',
                        'archived',
                        'archived_date',
                        'force_required',
                        'allow_guidance_override',
                        'question_type',
                        'is_child',
                        'version',
                        'default',
                        'required',
                        'title',
                        'guidance',
                        'options',
                        'component',
                        'validations',
                        'version_id',
                        'all_custodians',
                    ],
                ],
            ]);

        $content = $response->decodeResponseJson();
        $ids = array_column($content['data'], 'question_id');

        $this->assertContains($questionId, $ids);
    }

    /**
     * Returns a single question
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_question()
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
                'component' => 'TextArea',
                'validations' => [
                    'min' => 1,
                    'message' => 'Please enter a value'
                ],
                'title' => 'Test question',
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1,
                'is_child' => 0,
                'all_custodians' => true,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/questions/' . $content['data'], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'question_id',
                    'section_id',
                    'user_id',
                    'locked',
                    'archived',
                    'archived_date',
                    'force_required',
                    'allow_guidance_override',
                    'question_type',
                    'is_child',
                    'version',
                    'default',
                    'required',
                    'title',
                    'guidance',
                    'options',
                    'component',
                    'validations',
                    'version_id',
                    'all_custodians',
                ],
            ]);
    }

    /**
     * Returns a single question version
     *
     * @return void
     */
    public function test_the_application_can_list_a_single_question_version()
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
                    'min' => 1,
                    'message' => 'Please enter a value'
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();

        $response = $this->get('api/v1/questions/' . $content['data'], $this->header);
        $questionVersionId = $response->decodeResponseJson()['data']['version_id'];

        $response = $this->get('api/v1/questions/version/' . $questionVersionId, $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'question_id',
                    'version',
                    'default',
                    'required',
                    'question_json',
                ],
            ]);


    }

    /**
     * Fails to return a single question
     *
     * @return void
     */
    public function test_the_application_fails_to_list_a_single_question()
    {
        $beyondId = DB::table('question_bank_questions')->max('id') + 1;
        $response = $this->get('api/v1/questions/' . $beyondId, $this->header);
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    /**
     * Creates a new question
     *
     * @return void
     */
    public function test_the_application_can_create_a_question()
    {
        $countBefore = QuestionHasTeam::all()->count();
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
                    'min' => 1,
                    'message' => 'Please enter a value'
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

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        // Test creation of a custom question
        $countBefore = QuestionHasTeam::all()->count();
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'team_ids' => [1],
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'question_type' => 'CUSTOM',
                'all_custodians' => false,
                'options' => [],
                'component' => 'TextArea',
                'validations' => [
                    'min' => 1,
                    'message' => 'Please enter a value'
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
        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $this->assertEquals(QuestionHasTeam::all()->count(), $countBefore + 1);

        // now test with a nested set of questions
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                "default" => 0,
                "required" => false,
                "section_id" => 17,
                "user_id" => 1,
                "locked" => false,
                "archived" => false,
                "archived_date" => null,
                "force_required" => false,
                "allow_guidance_override" => true,
                "is_child" => 0,
                "question_type" => "STANDARD",
                'all_custodians' => true,
                "title" => "Please provide the legal basis to process confidential information",
                "guidance" => "Please confirm if consent is in place or underway for all disclosures of confidential information, if you have Section 251 exemption, or any other legal basis that you require for the project.\n\nFor England and Wales, please specify if Section 251 exemption is currently being sought and if so, please provide a Confidentiality Advisory group reference code.\n\nIn Scotland applications are required for the consented and unconsented use of data.\n",
                "options" => [
                    [
                        "label" => "Informed consent",
                        "children" => [
                            [
                                "label" => "Informed consent",
                                "title" => "Informed consent evidence",
                                "guidance" => "Please ensure a copy of the consent form(s) and patient information sheet have been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Section 251 support",
                        "children" => [
                            [
                                "label" => "Section 251 support",
                                "title" => "Section 251 exemption evidence",
                                "guidance" => "Please ensure a copy of the Section 251 exemption has been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [
                                    [
                                        "label" => "Yes"
                                    ],
                                    [
                                        "label" => "No"
                                    ]
                                ],
                                "force_required" => false,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "CAG reference",
                                "guidance" => "",
                                "required" => false,
                                "component" => "textInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => true,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "The section 251 approval enables the applicant to",
                                "guidance" => "Please indicate what the Section 251 exemption permits you to do as part of your project.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "validations" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true
                            ]
                        ]
                    ],
                    [
                        "label" => "Other",
                        "children" => [
                            [
                                "label" => "Other",
                                "title" => "If other, please specify",
                                "guidance" => "",
                                "required" => false,
                                "component" => "textInput",
                                "options" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Not applicable",
                        "children" => []
                    ]
                ],
                "component" => "RadioGroup",
                "validations" => []
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

    }

    /**
     * Fails to create a new question
     *
     * @return void
     */
    public function test_the_application_fails_to_create_a_question()
    {
        // Attempt to create question missing component and title
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                "default" => 0,
                "required" => false,
                "section_id" => 17,
                "user_id" => 1,
                "locked" => false,
                "archived" => false,
                "archived_date" => null,
                "force_required" => false,
                "allow_guidance_override" => true,
                "is_child" => 0,
                "question_type" => "STANDARD",
                'all_custodians' => true,
                "guidance" => "Please confirm if consent is in place or underway for all disclosures of confidential information, if you have Section 251 exemption, or any other legal basis that you require for the project.\n\nFor England and Wales, please specify if Section 251 exemption is currently being sought and if so, please provide a Confidentiality Advisory group reference code.\n\nIn Scotland applications are required for the consented and unconsented use of data.\n",
                "options" => [
                    [
                        "label" => "Informed consent",
                        "children" => [
                            [
                                "label" => "Informed consent",
                                "title" => "Informed consent evidence",
                                "guidance" => "Please ensure a copy of the consent form(s) and patient information sheet have been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Section 251 support",
                        "children" => [
                            [
                                "label" => "Section 251 support",
                                "title" => "Section 251 exemption evidence",
                                "guidance" => "Please ensure a copy of the Section 251 exemption has been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [
                                    [
                                        "label" => "Yes"
                                    ],
                                    [
                                        "label" => "No"
                                    ]
                                ],
                                "force_required" => false,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "CAG reference",
                                "guidance" => "",
                                "required" => false,
                                "component" => "textInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => true,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "The section 251 approval enables the applicant to",
                                "guidance" => "Please indicate what the Section 251 exemption permits you to do as part of your project.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "validations" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true
                            ]
                        ]
                    ],
                    [
                        "label" => "Other",
                        "children" => [
                            [
                                "label" => "Other",
                                "title" => "If other, please specify",
                                "guidance" => "",
                                "required" => false,
                                "component" => "textInput",
                                "options" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Not applicable",
                        "children" => []
                    ]
                ],
                "validations" => []
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJsonStructure([
                'status',
                'message',
                'errors',
            ]);

        // Attempt (and fail) to create a child question directly
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'options' => [],
                'component' => 'TextArea',
                'validations' => [
                    'min' => 1,
                    'message' => 'Please enter a value'
                ],
                'title' => 'Test question',
                'all_custodians' => true,
                'section_id' => 1,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1,
                'is_child' => 1
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJsonStructure([
                'message',
            ]);
    }

    /**
     * Tests that a question record can be updated
     *
     * @return void
     */
    public function test_the_application_can_update_a_question()
    {
        $countQuestionsBefore = QuestionBank::all()->count();
        $countQuestionVersionsBefore = QuestionBankVersion::all()->count();

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
                    'min' => 1,
                    'message' => 'Please enter a value'
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

        $this->assertEquals($countQuestionsBefore + 1, QuestionBank::all()->count());
        $this->assertEquals($countQuestionVersionsBefore + 1, QuestionBankVersion::all()->count());

        $content = $response->decodeResponseJson();

        $response = $this->json(
            'PUT',
            'api/v1/questions/' . $content['data'],
            [
                'section_id' => 2,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'options' => [],
                'all_custodians' => true,
                'component' => 'TextArea',
                'validations' => [
                    'min' => 1,
                    'message' => 'Please enter a value'
                ],
                'title' => 'Updated test question',
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'is_child' => 0,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['section_id'], 2);

        $version = QuestionBank::where('id', $content['data']['id'])
            ->first()
            ->latestVersion()
            ->first();
        // Test latest version is 2 and title is updated
        $this->assertEquals($version['version'], 2);
        $this->assertEquals($version['question_json']['title'], 'Updated test question');

        $this->assertEquals($countQuestionsBefore + 1, QuestionBank::all()->count());
        $this->assertEquals($countQuestionVersionsBefore + 2, QuestionBankVersion::all()->count());

        // now test with a nested set of questions - this will add 6 Questions and 6 QBVersions
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                "default" => 0,
                "required" => false,
                "section_id" => 17,
                "user_id" => 1,
                "locked" => false,
                "archived" => false,
                "archived_date" => null,
                "force_required" => false,
                "allow_guidance_override" => true,
                "is_child" => 0,
                "question_type" => "STANDARD",
                'all_custodians' => true,
                "title" => "Please provide the legal basis to process confidential information",
                "guidance" => "Please confirm if consent is in place or underway for all disclosures of confidential information, if you have Section 251 exemption, or any other legal basis that you require for the project.\n\nFor England and Wales, please specify if Section 251 exemption is currently being sought and if so, please provide a Confidentiality Advisory group reference code.\n\nIn Scotland applications are required for the consented and unconsented use of data.\n",
                "options" => [
                    [
                        "label" => "Informed consent",
                        "children" => [
                            [
                                "label" => "Informed consent",
                                "title" => "Informed consent evidence",
                                "guidance" => "Please ensure a copy of the consent form(s) and patient information sheet have been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Section 251 support",
                        "children" => [
                            [
                                "label" => "Section 251 support",
                                "title" => "Section 251 exemption evidence",
                                "guidance" => "Please ensure a copy of the Section 251 exemption has been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [
                                    [
                                        "label" => "Yes"
                                    ],
                                    [
                                        "label" => "No"
                                    ]
                                ],
                                "force_required" => false,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "CAG reference",
                                "guidance" => "",
                                "required" => false,
                                "component" => "textInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => true,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "The section 251 approval enables the applicant to",
                                "guidance" => "Please indicate what the Section 251 exemption permits you to do as part of your project.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "validations" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true
                            ]
                        ]
                    ],
                    [
                        "label" => "Other",
                        "children" => [
                            [
                                "label" => "Other",
                                "title" => "If other, please specify",
                                "guidance" => "",
                                "required" => false,
                                "component" => "textInput",
                                "options" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Not applicable",
                        "children" => []
                    ]
                ],
                "component" => "RadioGroup",
                "validations" => []
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $this->assertEquals($countQuestionsBefore + 7, QuestionBank::all()->count());
        $this->assertEquals($countQuestionVersionsBefore + 8, QuestionBankVersion::all()->count());

        // this will add 4 Questions (because the main parent is updated, the others are made new)
        // and 5 QBVersions (all have their versions bumped)
        $response = $this->json(
            'PUT',
            'api/v1/questions/' . $content['data'],
            [
                "default" => 0,
                "required" => false,
                "section_id" => 17,
                "user_id" => 1,
                "locked" => false,
                "archived" => false,
                "archived_date" => null,
                "force_required" => false,
                "allow_guidance_override" => true,
                "is_child" => 0,
                "question_type" => "STANDARD",
                'all_custodians' => true,
                "title" => "Please provide the legal basis to process confidential information",
                "guidance" => "Please confirm if consent is in place or underway for all disclosures of confidential information, if you have Section 251 exemption, or any other legal basis that you require for the project.\n\nFor England and Wales, please specify if Section 251 exemption is currently being sought and if so, please provide a Confidentiality Advisory group reference code.\n\nIn Scotland applications are required for the consented and unconsented use of data.\n",
                "options" => [
                    [
                        "label" => "Informed consent",
                        "children" => [
                            [
                                "label" => "Informed consent",
                                "title" => "Informed consent evidence",
                                "guidance" => "Please ensure a copy of the consent form(s) and patient information sheet have been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Section 251 support",
                        "children" => [
                            [
                                "label" => "Section 251 support",
                                "title" => "Section 251 exemption evidence",
                                "guidance" => "Please ensure a copy of the Section 251 exemption has been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [
                                    [
                                        "label" => "Yes"
                                    ],
                                    [
                                        "label" => "No"
                                    ]
                                ],
                                "force_required" => false,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "The section 251 approval enables the applicant to",
                                "guidance" => "Please indicate what the Section 251 exemption permits you to do as part of your project.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "validations" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true
                            ]
                        ]
                    ],
                    [
                        "label" => "Not applicable",
                        "children" => []
                    ]
                ],
                "component" => "RadioGroup",
                "validations" => []
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_OK.message')
        );

        $this->assertEquals($countQuestionsBefore + 10, QuestionBank::all()->count());
        $this->assertEquals($countQuestionVersionsBefore + 12, QuestionBankVersion::all()->count());

        // test that updating a child question fails
        $childQuestionId = QuestionBank::where('is_child', true)->first()->id;

        $response = $this->json(
            'PUT',
            'api/v1/questions/' . $childQuestionId,
            [
                "force_required" => false,
                "allow_guidance_override" => false,
                "options" => [
                    "yes",
                    "no"
                ],
                "component" => "RadioGroup",
                'all_custodians' => true,
                "validations" => [],
                "section_id" => 1,
                "title" => "Testing that updating a child fails",
                "guidance" => "This should fail",
                "required" => false,
                "default" => 1
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'))
            ->assertJsonStructure([
                'message',
            ]);
        $content = $response->decodeResponseJson();

        $this->assertEquals(
            $content['message'],
            'Cannot update a child question directly'
        );
    }

    /**
     * Tests that a question record can be edited
     *
     * @return void
     */
    public function test_the_application_can_edit_a_question()
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
                    'min' => 1,
                    'message' => 'Please enter a value'
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
        $content = $response->decodeResponseJson();
        $questionId = $content['data'];

        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $questionId,
            [
                'section_id' => 2,
                'title' => 'Updated test question'
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        // Test section id updated but another field (force required) is not
        $this->assertEquals($content['data']['section_id'], 2);
        $this->assertEquals($content['data']['force_required'], false);

        $version = QuestionBank::where('id', $content['data']['id'])
            ->first()
            ->latestVersion()
            ->first();
        // Test latest version is 1 (edit does not increase version, only update does),
        // title has been edited, and required has not been edited
        $this->assertEquals($version['version'], 1);
        $this->assertEquals($version['question_json']['title'], 'Updated test question');
        $this->assertEquals($version['required'], false);

        // Test that a new version is not created when question content is not updated
        // e.g. when a question is locked
        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $questionId,
            [
                'locked' => 1,
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['data']['locked'], true);

        $version = QuestionBank::where('id', $content['data']['id'])
            ->first()
            ->latestVersion()
            ->first();
        // Test latest version is still 1
        $this->assertEquals($version['version'], 1);

        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $questionId,
            [
                'validations' => [
                    'min' => 3
                ]
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $version = QuestionBank::where('id', $questionId)
            ->first()
            ->latestVersion()
            ->first();
        $this->assertEquals($version['question_json']['field']['validations']['min'], 3);

    }

    /**
     * Test if can update the status of a question
     *
     * @return void
     */
    public function test_the_application_can_update_the_status_of_a_question()
    {
        // create a question with children
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                "default" => 0,
                "required" => false,
                "section_id" => 17,
                "user_id" => 1,
                "locked" => false,
                "archived" => false,
                "archived_date" => null,
                "force_required" => false,
                "allow_guidance_override" => true,
                "is_child" => 0,
                "question_type" => "STANDARD",
                'all_custodians' => true,
                "title" => "Please provide the legal basis to process confidential information",
                "guidance" => "Please confirm if consent is in place or underway for all disclosures of confidential information, if you have Section 251 exemption, or any other legal basis that you require for the project.\n\nFor England and Wales, please specify if Section 251 exemption is currently being sought and if so, please provide a Confidentiality Advisory group reference code.\n\nIn Scotland applications are required for the consented and unconsented use of data.\n",
                "options" => [
                    [
                        "label" => "Informed consent",
                        "children" => [
                            [
                                "label" => "Informed consent",
                                "title" => "Informed consent evidence",
                                "guidance" => "Please ensure a copy of the consent form(s) and patient information sheet have been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "force_required" => true,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ]
                        ]
                    ],
                    [
                        "label" => "Section 251 support",
                        "children" => [
                            [
                                "label" => "Section 251 support",
                                "title" => "Section 251 exemption evidence",
                                "guidance" => "Please ensure a copy of the Section 251 exemption has been provided. Documents can be uploaded in the Additional Files section of this form.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [
                                    [
                                        "label" => "Yes"
                                    ],
                                    [
                                        "label" => "No"
                                    ]
                                ],
                                "force_required" => false,
                                "allow_guidance_override" => false,
                                "validations" => []
                            ],
                            [
                                "label" => "Section 251 support",
                                "title" => "The section 251 approval enables the applicant to",
                                "guidance" => "Please indicate what the Section 251 exemption permits you to do as part of your project.",
                                "required" => false,
                                "component" => "checkboxOptionsInput",
                                "options" => [],
                                "validations" => [],
                                "force_required" => false,
                                "allow_guidance_override" => true
                            ]
                        ]
                    ],
                    [
                        "label" => "Not applicable",
                        "children" => []
                    ]
                ],
                "component" => "RadioGroup",
                "validations" => []
            ],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $content = $response->decodeResponseJson();
        $questionId = $content['data'];

        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $questionId . '/lock',
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $response = $this->json(
            'GET',
            'api/v1/questions/' . $questionId,
            [],
            $this->header
        );

        $content = $response->decodeResponseJson();
        $this->assertTrue($content['data']['locked']);

        // get children ids and check they are also locked
        $childIds = [];
        foreach ($content['data']['options'] as $option) {
            foreach ($option['children'] as $child) {
                $childIds[] = $child['question_id'];
            }
        }

        foreach ($childIds as $id) {
            $response = $this->json(
                'GET',
                'api/v1/questions/' . $id,
                [],
                $this->header
            );
            $content = $response->decodeResponseJson();
            $this->assertTrue($content['data']['locked']);
        }

        // Test unlocking the same way
        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $questionId . '/unlock',
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $response = $this->json(
            'GET',
            'api/v1/questions/' . $questionId,
            [],
            $this->header
        );

        $content = $response->decodeResponseJson();
        $this->assertTrue(!$content['data']['locked']);

        // get children ids and check they are also locked
        $childIds = [];
        foreach ($content['data']['options'] as $option) {
            foreach ($option['children'] as $child) {
                $childIds[] = $child['question_id'];
            }
        }

        foreach ($childIds as $id) {
            $response = $this->json(
                'GET',
                'api/v1/questions/' . $id,
                [],
                $this->header
            );
            $content = $response->decodeResponseJson();
            $this->assertTrue(!$content['data']['locked']);
        }

        // test archiving
        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $questionId . '/archive',
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $response = $this->json(
            'GET',
            'api/v1/questions/' . $questionId,
            [],
            $this->header
        );

        $content = $response->decodeResponseJson();
        $this->assertTrue($content['data']['archived']);

        // get children ids and check they are also archived
        $childIds = [];
        foreach ($content['data']['options'] as $option) {
            foreach ($option['children'] as $child) {
                $childIds[] = $child['question_id'];
            }
        }

        foreach ($childIds as $id) {
            $response = $this->json(
                'GET',
                'api/v1/questions/' . $id,
                [],
                $this->header
            );
            $content = $response->decodeResponseJson();
            $this->assertTrue($content['data']['archived']);
        }

        // Test unarchiving the same way
        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $questionId . '/unarchive',
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $response = $this->json(
            'GET',
            'api/v1/questions/' . $questionId,
            [],
            $this->header
        );

        $content = $response->decodeResponseJson();
        $this->assertTrue(!$content['data']['archived']);

        // get children ids and check they are also archived
        $childIds = [];
        foreach ($content['data']['options'] as $option) {
            foreach ($option['children'] as $child) {
                $childIds[] = $child['question_id'];
            }
        }

        foreach ($childIds as $id) {
            $response = $this->json(
                'GET',
                'api/v1/questions/' . $id,
                [],
                $this->header
            );
            $content = $response->decodeResponseJson();
            $this->assertTrue(!$content['data']['archived']);
        }

        // test updating a child question's status fails
        $response = $this->json(
            'PATCH',
            'api/v1/questions/' . $childIds[0] . '/lock',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    /**
     * Tests it can delete a question
     *
     * @return void
     */
    public function test_the_application_can_delete_a_question()
    {
        $countBefore = QuestionHasTeam::all()->count();

        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'team_ids' => [1],
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'question_type' => 'CUSTOM',
                'all_custodians' => false,
                'options' => [],
                'component' => 'TextArea',
                'validations' => [
                    'min' => 1,
                    'message' => 'Please enter a value'
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
        $content = $response->decodeResponseJson();

        $this->assertEquals(QuestionHasTeam::all()->count(), $countBefore + 1);

        $response = $this->json(
            'DELETE',
            'api/v1/questions/' . $content['data'],
            [],
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
            ]);

        $this->assertEquals(QuestionHasTeam::all()->count(), $countBefore);

    }
}
