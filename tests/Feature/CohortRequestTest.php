<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\CohortRequest;
use Tests\Traits\Authorization;
use Database\Seeders\UserSeeder;
use Database\Seeders\SectorSeeder;
use Database\Seeders\CohortRequestSeed;
use Database\Seeders\MinimalUserSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CohortRequestTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/cohort_requests';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            SectorSeeder::class,
            CohortRequestSeed::class,
        ]);
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    /**
     * Get All Cohort Requests with success
     * 
     * @return void
     */
    public function test_get_all_cohort_requests_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'current_page',
            'data' => [
                0 => [
                    'id',
                    'user_id',
                    'user',
                    'request_status',
                    'cohort_status',
                    'request_expire_at',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'logs',
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

        $response->assertStatus(200);
    }

    /**
     * Get Cohort Request by id with success
     * 
     * @return void
     */
    public function test_get_cohort_request_by_id_with_success(): void
    {
        $randomCohortRequest = CohortRequest::inRandomOrder()->first();
        $randomCohortRequestId = $randomCohortRequest->id;

        $response = $this->json('GET', self::TEST_URL . '/' . $randomCohortRequestId, [], $this->header);

        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Create Cohort Request with success
     * 
     * @return void
     */
    public function test_create_cohort_request_with_success(): void
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL . '/' . $id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);
    }

    /**
     * Update Cohort Request with success
     * 
     * @return void
     */
    public function test_update_cohort_request_with_success(): void
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'request_status' => 'APPROVED',
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum - put.',
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL . '/' . $id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);
    }

    /**
     * Download Cohort Request Admin dashboard export with success
     * 
     * @return void
     */
    public function test_download_cohort_request_dashboard_with_success(): void
    {
        $responseDownload = $this->json(
            'GET',
            self::TEST_URL . '/export',
            [],
            $this->header,
        );

        $content = $responseDownload->streamedContent();
        $responseDownload->assertHeader('Content-Disposition', 'attachment;filename="Cohort_Discovery_Admin.csv"');
        $this->assertEquals(
            substr($content, 0, 9),
            "\"User ID\""
        );
    }
    
    /**
     * Delete Cohort Request with success
     * 
     * @return void
     */
    public function test_delete_cohort_request_with_success(): void
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum.',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'request_status' => 'APPROVED',
                'details' => 'Praesentium ut et quae suscipit ut quo adipisci. Enim ut tenetur ad omnis ut consequatur. Aliquid officiis expedita rerum - put.',
            ],
            $this->header,
        );

        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        // get one
        $responseGetOne = $this->json('GET', self::TEST_URL . '/' . $id, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseGetOne->assertStatus(200);

        // delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $id,
            [],
            $this->header,
        );

        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }
}
