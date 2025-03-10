<?php

namespace Tests\Feature;

use Config;

use App\Models\Dur;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Tool;
use App\Models\User;
use App\Models\Sector;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Publication;
use Database\Seeders\DurSeeder;
use Database\Seeders\TagSeeder;
use App\Http\Enums\TeamMemberOf;
use Database\Seeders\ToolSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use App\Models\DurHasDatasetVersion;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\DurHasToolSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\PublicationSeeder;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\CollectionHasUserSeeder;
use Database\Seeders\DurHasPublicationSeeder;
use Database\Seeders\ProgrammingPackageSeeder;
use Database\Seeders\PublicationHasToolSeeder;
use Database\Seeders\ProgrammingLanguageSeeder;
use Database\Seeders\DurHasDatasetVersionSeeder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\PublicationHasDatasetVersionSeeder;

class DurTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/dur';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';

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
            CategorySeeder::class,
            TypeCategorySeeder::class,
            ProgrammingLanguageSeeder::class,
            ProgrammingPackageSeeder::class,
            LicenseSeeder::class,
            TagSeeder::class,
            ApplicationSeeder::class,
            CollectionSeeder::class,
            CollectionHasUserSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            ToolSeeder::class,
            DurSeeder::class,
            PublicationSeeder::class,
            PublicationHasDatasetVersionSeeder::class,
            PublicationHasToolSeeder::class,
            DurHasPublicationSeeder::class,
            DurHasToolSeeder::class,
            DurHasDatasetVersionSeeder::class,
        ]);
    }
    /**
     * Get All Data Use Registers with success
     *
     * @return void
     */
    public function test_get_all_dur_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'non_gateway_datasets',
                    'non_gateway_applicants',
                    'funders_and_sponsors',
                    'other_approval_committees',
                    'gateway_outputs_tools',
                    'gateway_outputs_papers',
                    'non_gateway_outputs',
                    'project_title',
                    'project_id_text',
                    'organisation_name',
                    'organisation_sector',
                    'lay_summary',
                    'technical_summary',
                    'latest_approval_date',
                    'manual_upload',
                    'rejection_reason',
                    'sublicence_arrangements',
                    'public_benefit_statement',
                    'data_sensitivity_level',
                    'project_start_date',
                    'project_end_date',
                    'access_date',
                    'accredited_researcher_status',
                    'confidential_data_description',
                    'dataset_linkage_description',
                    'duty_of_confidentiality',
                    'legal_basis_for_data_article6',
                    'legal_basis_for_data_article9',
                    'national_data_optout',
                    'organisation_id',
                    'privacy_enhancements',
                    'request_category_type',
                    'request_frequency',
                    'access_type',
                    'mongo_object_dar_id',
                    'enabled',
                    'last_activity',
                    'counter',
                    'mongo_object_id',
                    'mongo_id',
                    'user_id',
                    'team_id',
                    'created_at',
                    'updated_at',
                    'datasets',
                    'publications',
                    'tools',
                    'keywords',
                    'application',
                    'team',
                    'user',
                    'status',
                ],
            ],
            'current_page',
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
     * Get Dur by Id with success
     *
     * @return void
     */
    public function test_get_dur_by_id_with_success(): void
    {
        $durId = (int) Dur::all()->where('status', 'ACTIVE')->random()->id;
        $dataset = Dataset::all()->where('status', 'ACTIVE')->random();
        $datasetVersionId = $dataset->versions()->first()->id;
        DurHasDatasetVersion::create([
            'dur_id' => $durId,
            'dataset_version_id' => $datasetVersionId,
        ]);

        $response = $this->json('GET', self::TEST_URL . '/' . $durId, [], $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'non_gateway_datasets',
                    'non_gateway_applicants',
                    'funders_and_sponsors',
                    'other_approval_committees',
                    'gateway_outputs_tools',
                    'gateway_outputs_papers',
                    'non_gateway_outputs',
                    'project_title',
                    'project_id_text',
                    'organisation_name',
                    'organisation_sector',
                    'lay_summary',
                    'technical_summary',
                    'latest_approval_date',
                    'manual_upload',
                    'rejection_reason',
                    'sublicence_arrangements',
                    'public_benefit_statement',
                    'data_sensitivity_level',
                    'project_start_date',
                    'project_end_date',
                    'access_date',
                    'accredited_researcher_status',
                    'confidential_data_description',
                    'dataset_linkage_description',
                    'duty_of_confidentiality',
                    'legal_basis_for_data_article6',
                    'legal_basis_for_data_article9',
                    'national_data_optout',
                    'organisation_id',
                    'privacy_enhancements',
                    'request_category_type',
                    'request_frequency',
                    'access_type',
                    'mongo_object_dar_id',
                    'enabled',
                    'last_activity',
                    'counter',
                    'mongo_object_id',
                    'mongo_id',
                    'user_id',
                    'team_id',
                    'created_at',
                    'updated_at',
                    'datasets' => [
                        0 => [
                            'id',
                            'shortTitle',
                        ]
                    ],
                    'publications',
                    'tools',
                    'keywords',
                    'applications',
                    'team',
                    'user',
                    'application_id',
                    'applications',
                    'status',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Dur with success
     *
     * @return void
     */
    public function test_add_new_dur_with_success(): void
    {
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;

        /*
        * use the endpoint /api/v1/datause/count to find unique values of the field 'status'
        */
        $responseCount = $this->json(
            'GET',
            self::TEST_URL .
            '/count/status?team_id=' . $teamId,
            [],
            $this->header
        );
        $responseCount->assertStatus(200);
        $teamCountActiveBefore = array_key_exists('ACTIVE', $responseCount['data']) ? $responseCount['data']['ACTIVE'] : 0;
        $teamCountDraftBefore = array_key_exists('DRAFT', $responseCount['data']) ? $responseCount['data']['DRAFT'] : 0;

        $countBefore = Dur::count();

        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'tools' => $this->generateTools(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );

        $response->assertStatus(201);

        $countAfter = Dur::count();
        $countNewRow = $countAfter - $countBefore;
        $this->assertTrue((bool) $countNewRow, 'Response was successfully');

        // Check that the sector has been correctly mapped.
        $dur_index = $response->json()['data'];
        $this->assertEquals(
            Dur::where('id', $dur_index)->first()['sector_id'],
            Sector::where('name', 'Academia')->first()['id']
        );


        /*
        * compare the counts per status to before the ACTIVE one was added
        */
        $responseCount = $this->json(
            'GET',
            self::TEST_URL .
            '/count/status?team_id=' . $teamId,
            [],
            $this->header
        );
        $responseCount->assertStatus(200);
        $teamCountActiveAfter = array_key_exists('ACTIVE', $responseCount['data']) ? $responseCount['data']['ACTIVE'] : 0;
        $teamCountDraftAfter = array_key_exists('DRAFT', $responseCount['data']) ? $responseCount['data']['DRAFT'] : 0;

        $this->assertEquals($teamCountDraftAfter, $teamCountDraftBefore);
        $this->assertEquals($teamCountActiveAfter, $teamCountActiveBefore + 1);
    }

    /**
     * Create and Update Dur with success
     *
     * @return void
     */
    public function test_update_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );
        $response->assertStatus(201);
        $durId = (int) $response['data'];

        $countAfter = Dur::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');

        // Check that the sector has been correctly mapped.
        $dur_index = $response->json()['data'];
        $this->assertEquals(
            Dur::where('id', $dur_index)->first()['sector_id'],
            Sector::where('name', 'Academia')->first()['id']
        );
        // update
        $mockDataUpdate = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01','External Dataset 02', 'External Dataset 03'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'Commercial',
            'status' => 'ACTIVE',
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $durId,
            $mockDataUpdate,
            $this->header
        );
        $responseUpdate->assertStatus(200);

        // Check that the sector has been correctly mapped.
        $dur_index = $response->json()['data'];
        $this->assertEquals(
            Dur::where('id', $dur_index)->first()['sector_id'],
            Sector::where('name', 'Industry')->first()['id']
        );
    }

    /**
     * Create and Update Dur with success
     *
     * @return void
     */
    public function test_update_make_draft_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );
        $response->assertStatus(201);
        $durId = (int) $response['data'];

        $countAfter = Dur::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');

        // Check that the sector has been correctly mapped.
        $dur_index = $response->json()['data'];
        $this->assertEquals(
            Dur::where('id', $dur_index)->first()['sector_id'],
            Sector::where('name', 'Academia')->first()['id']
        );
        // update
        $mockDataUpdate = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01','External Dataset 02', 'External Dataset 03'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'Commercial',
            'status' => 'DRAFT',
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $durId,
            $mockDataUpdate,
            $this->header
        );
        $responseUpdate->assertStatus(200);

        // Check that the sector has been correctly mapped.
        $dur_index = $response->json()['data'];
        $this->assertEquals(
            Dur::where('id', $dur_index)->first()['sector_id'],
            Sector::where('name', 'Industry')->first()['id']
        );
    }


    /**
     * Create and Update and Edit Dur with success
     *
     * @return void
     */
    public function test_edit_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'tools' => $this->generateTools(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );
        $response->dump();
        $response->assertStatus(201);
        $durId = (int) $response['data'];

        $countAfter = Dur::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');

        // update
        $mockDataUpdate = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02', 'External Dataset 03'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'status' => 'ACTIVE',
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $durId,
            $mockDataUpdate,
            $this->header
        );
        $responseUpdate->assertStatus(200);

        // update
        $mockDataEdit = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'organisation_sector' => 'Commercial',
        ];
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $durId,
            $mockDataEdit,
            $this->header
        );
        $responseEdit->assertStatus(200);

        // Check that the sector has been correctly mapped.
        $dur_index = $response->json()['data'];
        $this->assertEquals(
            Dur::where('id', $dur_index)->first()['sector_id'],
            Sector::where('name', 'Industry')->first()['id']
        );
    }

    /**
     * Create and delete Dur with success
     *
     * @return void
     */
    public function test_delete_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'tools' => $this->generateTools(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );
        $response->assertStatus(201);
        $durId = (int) $response['data'];

        $countAfter = Dur::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');

        // delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $durId,
            [],
            $this->header
        );
        $responseDelete->assertStatus(200);
    }

    /**
     * Create, delete, unarchive (un-delete), and delete Dur with success
     *
     * @return void
     */
    public function test_create_archive_unarchive_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'tools' => $this->generateTools(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'status' => 'ACTIVE',
        ];

        $responseIns = $this->json(
            'POST',
            self::TEST_URL . '/',
            $mockData,
            $this->header
        );
        $responseIns->assertStatus(201);
        $responseIns->assertJsonStructure([
            'message',
            'data',
        ]);

        $this->assertEquals(
            $responseIns['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );
        $dueIdInsert = $responseIns['data'];

        // Archive
        $mockDataEdit = [
            'status' => 'ARCHIVED',
        ];
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $dueIdInsert,
            $mockDataEdit,
            $this->header
        );
        $responseEdit->assertStatus(200);

        // Unarchive tool
        $responseUnarchive = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $dueIdInsert . '?unarchive',
            ['status' => 'ACTIVE'],
            $this->header
        );
        $responseUnarchive->assertJsonStructure([
            'message',
            'data',
        ]);

        $responseUnarchive->assertStatus(200);

        // Verify that the unarchived dur has deleted_at == null
        $durData = $responseUnarchive['data'];
        $this->assertNull($durData['deleted_at']);
        $this->assertEquals($durData['status'], 'ACTIVE');

        // Delete again
        $responseDeleteAgain = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $dueIdInsert,
            [],
            $this->header
        );
        $responseDeleteAgain->assertStatus(200);
    }


    public function test_download_dur_table_with_success(): void
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // create team
        // First create a notification to be used by the new team
        $responseNotification = $this->json(
            'POST',
            self::TEST_URL_NOTIFICATION,
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => 3,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            $this->header,
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            self::TEST_URL_TEAM,
            [
                'name' => 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}'),
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'notifications' => [$notificationID],
                'users' => [],
            ],
            $this->header,
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreateTeam = $responseCreateTeam->decodeResponseJson();
        $teamId = $contentCreateTeam['data'];

        // create user
        $responseCreateUser = $this->json(
            'POST',
            self::TEST_URL_USER,
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => 'firstname.lastname.123456789@test.com',
                'password' => 'Passw@rd1!',
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => 1234566,
                'mongo_object_id' => "12345abcde",
            ],
            $this->header,
        );
        $responseCreateUser->assertStatus(201);
        $contentCreateUser = $responseCreateUser->decodeResponseJson();
        $userId = $contentCreateUser['data'];

        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'tools' => $this->generateTools(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );
        $response->assertStatus(201);
        $durId = (int) $response['data'];

        $countAfter = Dur::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');

        $responseDownload = $this->json(
            'GET',
            self::TEST_URL . '/export',
            [],
            $this->header,
        );

        // dd($responseDownload->decodeResponseJson());

        $content = $responseDownload->streamedContent();
        $this->assertMatchesRegularExpression('/Non-Gateway Datasets/', $content);
    }

    /**
     * Create and Upload Dur with success
     *
     * @return void
     */
    public function test_upload_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $mockData = [
            'datasets' => $this->generateUploadDatasets(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'project_title' => 'Upload - Health, death, and cancers in people with learning disabilities and people with autism.',
            'project_id_text' => 'eDRIS-1819-0051',
            'organisation_name' => 'University of Glasgow',
            'organisation_sector' => 'Academia',
            'non_gateway_applicants' => 'Skye Harvey | Leora Bartell',
            'applicant_id' => (int) $userId,
            'project_start_date' => '2020-03-23T00:00:00',
            'project_end_date' => '2025-04-30T00:00:00',
            'latest_approval_date' => '2020-04-14T00:00:00',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL . '/upload',
            $mockData,
            $this->header
        );
        $response->assertStatus(201);
        $durId = (int) $response['data'];

        $dur = Dur::where(['id' => $durId])->first();

        $this->assertTrue((bool) $dur, 'Response was successfully');
    }

    public function test_can_download_template_file()
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // Mock the storage disk
        Storage::fake('mock');

        // Put a fake file in the mock disk
        $filePath = 'data_use_template_file.xlsx';
        Storage::disk('mock')->put($filePath, 'file content');

        // Mock the config
        Config::set('mock_data.data_use_upload_template', $filePath);

        // Make the request
        $response = $this->get('/api/v1/dur/template');

        // Assert the file is downloaded
        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=' . $filePath);

        // Clean up
        Storage::disk('mock')->delete($filePath);
    }

    public function test_download_template_file_with_file_not_found()
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // Mock the config
        Config::set('mock_data.data_use_upload_template', 'non_existent_file.xlsx');

        // Make the request
        $response = $this->get('/api/v1/dur/template');

        // Assert the file is not found
        $response->assertStatus(404);
        $response->assertJson(['error' => 'File not found.']);
    }

    public function test_the_application_can_search_on_project_id(): void
    {
        $teamId = (int) Team::all()->random()->id;

        $mockData = [
            'project_id_text' => '12345-67890',
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'tools' => $this->generateTools(),
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );

        $response->assertStatus(201);

        $response = $this->json(
            'GET',
            self::TEST_URL . '?project_id=' . $mockData['project_id_text'],
            $this->header
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson()['data'];
        $this->assertTrue($content[0]['project_id_text'] === $mockData['project_id_text']);
        $this->assertTrue(count($content) === 1);
    }

    private function generateKeywords()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $return[] = Keyword::where(['enabled' => 1])->get()->random()->name;
        }

        return array_unique($return);
    }

    private function generateTools()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $return[] = Tool::where(['enabled' => 1])->get()->random()->id;
        }

        return array_unique($return);
    }

    private function generateDatasets()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Dataset::all()->random()->id;
            $temp['reason'] = htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
            $temp['is_locked'] = fake()->randomElement([0, 1]);
            $return[] = $temp;
        }

        return $return;
    }

    private function generateUploadDatasets()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Dataset::all()->random()->id;
            $return[] = $temp;
        }

        return $return;
    }

    private function generatePublications()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $temp = [];
            $temp['id'] = Publication::all()->random()->id;
            $return[] = $temp;
        }

        return $return;
    }
}
