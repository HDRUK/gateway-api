<?php

namespace Tests\Feature;

use App\Jobs\SendEmailJob;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\EnquiryThreadSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SpatialCoverageSeeder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class EnquiryThreadTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/enquiry_threads';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            EmailTemplateSeeder::class,
            EnquiryThreadSeeder::class,
        ]);

        Queue::fake([
            SendEmailJob::class,
        ]);
    }

    /**
     * Get All Enquiry Threads with success
     *
     * @return void
     */
    public function test_get_all_enquiry_threads_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'data' => [
                    0 => [
                        'id',
                        'user_id',
                        'team_id',
                        'project_title',
                        'is_dar_dialogue',
                        'is_dar_status',
                        'is_general_enquiry',
                        'is_feasibility_enquiry',
                        'enabled',
                    ],
                ]
            ]
        ]);
    }

    /**
     * Get Email Template by Id with success
     *
     * @return void
     */
    public function test_get_enquiry_thread_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);
        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'user_id',
                    'team_id',
                    'project_title',
                    'unique_key',
                    'is_dar_dialogue',
                    'is_dar_status',
                    'is_general_enquiry',
                    'is_feasibility_enquiry',
                    'enabled',
                ],
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new DAR Enqiry Thread with success
     *
     * @return void
     */
    public function test_add_new_dar_enquiry_thread_with_success(): void
    {
        $body = [
            'project_title' => 'Test DAR project',
            'from' => 'example.test@hdruk.ac.uk',
            'contact_number' => '000111444',
            'is_dar_dialogue' => true,
            'is_dar_status' => false,
            'is_feasibility_enquiry' => false,
            'is_general_enquiry' => false,
            'datasets' => [
                0 => [
                    'dataset_id' => 1,
                    'interest_type' => 'PRIMARY'
                ],
            ],
            'query' => 'What should I enter for this question?'
        ];
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $body,
            $this->header
        );

        dd($response);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);
    }
}
