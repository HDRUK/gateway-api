<?php

namespace Tests\Feature;

use Config;
use App\Http\Enums\TeamMemberOf;
use App\Jobs\SendEmailJob;
use App\Models\Dataset;
use App\Models\EnquiryThread;
use App\Models\Team;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\EnquiryThreadSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\SDENetworkConciergeSeeder;

use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Support\Facades\Queue;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class EnquiryThreadTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/enquiry_threads';

    protected $metadata;
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
            EmailTemplateSeeder::class,
            EnquiryThreadSeeder::class,
            SDENetworkConciergeSeeder::class,

        ]);

        $this->metadata = $this->getMetadata();
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
                        'is_dar_review',
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
                    'is_dar_review',
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
        // Create user with dar.reviewer role
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
        $responseTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $content = $responseTeam->decodeResponseJson();
        $teamId = $content['data'];
        $team = Team::findOrFail($teamId);

        // assign roles to user
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

        $metadata = $this->getMetadata();
        $metadata['metadata']['summary']['publisher'] = [
            'name' => $team->name,
            'gatewayId' => $team->id
        ];
        $responseCreateDataset = $this->json(
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
        $responseCreateDataset->assertStatus(201);
        $datasetId = $responseCreateDataset['data'];

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
                    'dataset_id' => $datasetId,
                    'interest_type' => 'PRIMARY'
                ],
            ],
            'message' => 'What should I enter for this question?'
        ];
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $body,
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        // Add another team and dataset and test DAR enquiry for multiple datasets
        // Create team for the user to belong to
        $responseTeamTwo = $this->json(
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
        $responseTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $content = $responseTeamTwo->decodeResponseJson();
        $teamIdTwo = $content['data'];

        // assign roles to user
        $url = '/api/v1/teams/' . $teamIdTwo . '/users';
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
        $responseCreateDatasetTwo = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamIdTwo,
                'user_id' => $uniqueUserId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseCreateDatasetTwo->assertStatus(201);
        $datasetIdTwo = $responseCreateDatasetTwo['data'];

        $numThreadsBefore = EnquiryThread::count();

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
                    'dataset_id' => $datasetId,
                    'interest_type' => 'PRIMARY'
                ],
                1 => [
                    'dataset_id' => $datasetIdTwo,
                    'interest_type' => 'PRIMARY'
                ],
            ],
            'message' => 'What should I enter for this question?'
        ];
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $body,
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data',
            'all_threads',
        ]);

        $numThreadsAfter = EnquiryThread::count();

        $this->assertEquals($numThreadsAfter, $numThreadsBefore + 2);
    }
    /**
     * Create an enquiry with SDE datasets
     *
     * @return void
     */
    public function test_add_new_sde_enquiry_thread_with_success(): void
    {
        $uniqueUserId = $this->createUser();
        $teamId = $this->createTeam([$uniqueUserId]);
        $team = Team::findOrFail($teamId);

        // assign roles to user
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

        // Add the team to the SDE network
        $response = $this->json(
            'POST',
            '/api/v1/data_provider_colls',
            [
                'name' => 'SDE Network Test',
                'summary' => 'SDE Network Test',
                'img_url' => 'https://doesntexist.com/img.jpeg',
                'enabled' => 1,
                'team_ids' => [$teamId],
            ],
            $this->header,
        );
        $response->assertStatus(201);

        $responseCreateDataset = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => 1,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $datasetId = $responseCreateDataset['data'];

        // Create second team and a dataset for them
        $teamIdTwo = $this->createTeam([$uniqueUserId]);
        $url = '/api/v1/teams/' . $teamIdTwo . '/users';
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
        $responseCreateDatasetTwo = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamIdTwo,
                'user_id' => $uniqueUserId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );
        $responseCreateDatasetTwo->assertStatus(201);
        $datasetIdTwo = $responseCreateDatasetTwo['data'];

        Queue::fake();
        $numThreadsBefore = EnquiryThread::count();

        // Multi-dataset enquiry directs to concierge
        $body = [
            'project_title' => 'Test Enquiry',
            'from' => 'example.test@hdruk.ac.uk',
            'contact_number' => '000111444',
            'is_dar_dialogue' => false,
            'is_dar_status' => false,
            'is_feasibility_enquiry' => false,
            'is_general_enquiry' => true,
            'datasets' => [
                0 => [
                    'dataset_id' => $datasetId,
                    'interest_type' => 'PRIMARY'
                ],
                1 => [
                    'dataset_id' => $datasetIdTwo,
                    'interest_type' => 'PRIMARY'
                ],
            ],
            'message' => 'What should I enter for this question?'
        ];
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $body,
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $numThreadsAfter = EnquiryThread::count();

        // Test that an email was sent to the network concierge
        Queue::assertPushed(function (SendEmailJob $email) {
            return $email->to['to']['name'] === 'SDE Network Concierge';
        });

        $this->assertEquals($numThreadsAfter, $numThreadsBefore + 1);

        Queue::fake();

        // Single dataset enquiry directs to the dar manager
        $body = [
            'project_title' => 'Test Enquiry - single SDE',
            'from' => 'example.test@hdruk.ac.uk',
            'contact_number' => '000111444',
            'is_dar_dialogue' => false,
            'is_dar_status' => false,
            'is_feasibility_enquiry' => false,
            'is_general_enquiry' => true,
            'datasets' => [
                0 => [
                    'dataset_id' => $datasetId,
                    'interest_type' => 'PRIMARY'
                ],
            ],
            'message' => 'What should I enter for this question?'
        ];
        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            $body,
            $this->header
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);

        $numThreadsAfter = EnquiryThread::count();

        // Test that an email was NOT sent to the network concierge
        Queue::assertPushed(function (SendEmailJob $email) {
            return $email->to['to']['name'] !== 'SDE Network Concierge';
        });
    }

    private function createUser(): int
    {
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
        return $uniqueUserId;
    }

    private function createTeam(array $users = []): int
    {
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
                'users' => $users,
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
}
