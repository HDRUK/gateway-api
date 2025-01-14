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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class QuestionBankTest extends TestCase
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
                        'id',
                        'created_at',
                        'updated_at',
                        'deleted_at',
                        'section_id',
                        'user_id',
                        'locked',
                        'archived',
                        'archived_date',
                        'force_required',
                        'allow_guidance_override',
                        'question_type',
                        'is_child',
                        'latest_version',
                        'versions' => [
                            0 => ['child_versions']
                        ],
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

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'section_id',
                    'user_id',
                    'locked',
                    'archived',
                    'archived_date',
                    'force_required',
                    'allow_guidance_override',
                    'question_type',
                    'is_child',
                    'latest_version',
                        'latest_version',
                        'versions' => [
                            0 => ['child_versions']
                        ],
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
                'team_id' => [1],
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'question_type' => 'CUSTOM',
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
                "section_id" => 1,
                "force_required" => false,
                "allow_guidance_override" => false,
                "locked" => false,
                "archived" => false,
                "is_child" => false,
                "field" => [
                    "options" => [
                        "yes",
                        "no"
                    ],
                    "component" => "RadioGroup",
                    "validations" => []
                ],
                "title" => "Is this a test?",
                "guidance" => "You tell me",
                "required" => false,
                "default" => 0,
                "children" => [
                    "yes" => [
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "options" => [
                                    "yes",
                                    "no"
                                ],
                                "component" => "RadioGroup",
                                "validations" => []
                            ],
                            "title" => "Are you sure it is?",
                            "guidance" => "Second chance to confirm",
                            "required" => false,
                            "default" => 1
                        ],
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "component" => "TextField",
                                "validations" => []
                            ],
                            "title" => "And why do you say that?",
                            "guidance" => "Please explain",
                            "required" => false,
                            "default" => 1
                        ]
                    ],
                    "no" => [
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "options" => [
                                    "yes",
                                    "no"
                                ],
                                "component" => "RadioGroup",
                                "validations" => []
                            ],
                            "title" => "Are you sure it isn't?",
                            "guidance" => "Second chance to deny",
                            "required" => false,
                            "default" => 0
                        ],
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "component" => "TextField",
                                "validations" => []
                            ],
                            "title" => "And why do you say that?",
                            "guidance" => "Please explain",
                            "required" => false,
                            "default" => 1
                        ]
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
        // Attempt to create question missing field and title
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'guidance' => 'Something helpful',
                'required' => 0,
                'default' => 0,
                'version' => 1
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
                'title' => 'Test question',
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
        $this->assertEquals(json_decode($version['question_json'], true)['title'], 'Updated test question');

        $this->assertEquals($countQuestionsBefore + 1, QuestionBank::all()->count());
        $this->assertEquals($countQuestionVersionsBefore + 2, QuestionBankVersion::all()->count());

        // now test with a nested set of questions - this will add 5 Questions and 5 QBVersions
        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                "section_id" => 1,
                "force_required" => false,
                "allow_guidance_override" => false,
                "locked" => false,
                "archived" => false,
                "is_child" => false,
                "field" => [
                    "options" => [
                        "yes",
                        "no"
                    ],
                    "component" => "RadioGroup",
                    "validations" => []
                ],
                "title" => "Is this a test?",
                "guidance" => "You tell me",
                "required" => false,
                "default" => 0,
                "children" => [
                    "yes" => [
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "options" => [
                                    "yes",
                                    "no"
                                ],
                                "component" => "RadioGroup",
                                "validations" => []
                            ],
                            "title" => "Are you sure it is?",
                            "guidance" => "Second chance to confirm",
                            "required" => false,
                            "default" => 1
                        ],
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "component" => "TextField",
                                "validations" => []
                            ],
                            "title" => "And why do you say that?",
                            "guidance" => "Please explain",
                            "required" => false,
                            "default" => 1
                        ]
                    ],
                    "no" => [
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "options" => [
                                    "yes",
                                    "no"
                                ],
                                "component" => "RadioGroup",
                                "validations" => []
                            ],
                            "title" => "Are you sure it isn't?",
                            "guidance" => "Second chance to deny",
                            "required" => false,
                            "default" => 0
                        ],
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "component" => "TextField",
                                "validations" => []
                            ],
                            "title" => "And why do you say that?",
                            "guidance" => "Please explain",
                            "required" => false,
                            "default" => 1
                        ]
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
        $this->assertEquals(
            $content['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $this->assertEquals($countQuestionsBefore + 6, QuestionBank::all()->count());
        $this->assertEquals($countQuestionVersionsBefore + 7, QuestionBankVersion::all()->count());

        // this will add 4 Questions (because the main parent is updated, the others are made new)
        // and 5 QBVersions (all have their versions bumped)
        $response = $this->json(
            'PUT',
            'api/v1/questions/' . $content['data'],
            [
                "section_id" => 1,
                "force_required" => false,
                "allow_guidance_override" => false,
                "locked" => false,
                "archived" => false,
                "is_child" => false,
                "field" => [
                    "options" => [
                        "yes",
                        "no"
                    ],
                    "component" => "RadioGroup",
                    "validations" => []
                ],
                "title" => "Is this a test?",
                "guidance" => "You tell me",
                "required" => false,
                "default" => 0,
                "children" => [
                    "yes" => [
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "options" => [
                                    "yes",
                                    "no"
                                ],
                                "component" => "RadioGroup",
                                "validations" => []
                            ],
                            "title" => "Are you sure it is? (updated)",
                            "guidance" => "Second chance to confirm",
                            "required" => false,
                            "default" => 1
                        ]
                    ],
                    "no" => [
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "options" => [
                                    "yes",
                                    "no"
                                ],
                                "component" => "RadioGroup",
                                "validations" => []
                            ],
                            "title" => "Are you sure it isn't? (updated)",
                            "guidance" => "Second chance to deny",
                            "required" => false,
                            "default" => 0
                        ],
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "component" => "TextField",
                                "validations" => []
                            ],
                            "title" => "And why do you say that? (updated)",
                            "guidance" => "Please explain",
                            "required" => false,
                            "default" => 1
                        ],
                        [
                            "force_required" => false,
                            "allow_guidance_override" => false,
                            "field" => [
                                "component" => "TextField",
                                "validations" => []
                            ],
                            "title" => "Is that all? (updated)",
                            "guidance" => "Please explain",
                            "required" => false,
                            "default" => 1
                        ]
                    ]
                ]
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
                "field" => [
                    "options" => [
                        "yes",
                        "no"
                    ],
                    "component" => "RadioGroup",
                    "validations" => []
                ],
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
        $questionJson = json_decode($version['question_json'], true);
        $this->assertEquals($questionJson['title'], 'Updated test question');
        $this->assertEquals($questionJson['required'], false);

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
    }

    /**
     * Tests it can delete a question
     *
     * @return void
     */
    public function test_it_can_delete_a_question()
    {
        $countBefore = QuestionHasTeam::all()->count();

        $response = $this->json(
            'POST',
            'api/v1/questions',
            [
                'section_id' => 1,
                'user_id' => 1,
                'team_id' => [1],
                'force_required' => 0,
                'allow_guidance_override' => 1,
                'question_type' => 'CUSTOM',
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
