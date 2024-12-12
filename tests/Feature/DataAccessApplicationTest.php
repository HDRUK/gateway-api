<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;
use Tests\TestCase;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\DataAccessApplicationSeeder;

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

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            DataAccessApplicationSeeder::class,
        ]);
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
                'approval_status' => 'APPROVED_COMMENTS'
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
                'approval_status' => 'APPROVED_COMMENTS'
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
            [],
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
}
