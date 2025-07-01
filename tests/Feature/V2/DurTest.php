<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dur;
use App\Models\Tool;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Publication;
use Illuminate\Support\Carbon;
use Database\Seeders\DurSeeder;
use Tests\Traits\Authorization;
use Tests\Traits\Helpers;
use Tests\Traits\MockExternalApis;
use Database\Seeders\ToolSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\LicenseSeeder;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\PublicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\TypeCategorySeeder;
use Database\Seeders\DatasetVersionSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DurTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use Helpers;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DUR_V2 = '/api/v2/dur';
    public const TEST_URL_TEAM = '/api/v1/teams';
    public const TEST_URL_NOTIFICATION = '/api/v1/notifications';
    public const TEST_URL_USER = '/api/v1/users';
    public const EXPECTED_DUR_RESPONSE_DATA = [
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
            ];

    protected $metadata;
    protected $metadataAlt;
    protected $nonAdminJwt;
    protected $nonAdminUser;
    protected $headerNonAdmin;
    protected $nonAdmin2User;
    protected $nonAdmin2Jwt;
    protected $headerNonAdmin2;

    public function setUp(): void
    {
        $this->commonSetUp();

        Dur::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            SpatialCoverageSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            DurSeeder::class,
            PublicationSeeder::class,
            LicenseSeeder::class,
            TypeCategorySeeder::class,
            ToolSeeder::class,
            CollectionSeeder::class,
            EmailTemplateSeeder::class,
        ]);

        // Generate non-admin user for general usage
        $this->authorisationUser(false);
        $this->nonAdminJwt = $this->getAuthorisationJwt(false);
        $this->nonAdminUser = $this->getUserFromJwt($this->nonAdminJwt);
        $this->headerNonAdmin = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdminJwt,
        ];

        // generate jwt for a different user
        // This user can be used to test as a member of another team
        $this->authorisationUser(false, 2);
        $this->nonAdmin2Jwt = $this->getAuthorisationJwt(false, 2);
        $this->nonAdmin2User = $this->getUserFromJwt($this->nonAdmin2Jwt);
        $this->headerNonAdmin2 = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->nonAdmin2Jwt,
        ];
    }

    /**
     * Get All active DURs with success
     *
     * @return void
     */
    public function test_get_all_active_durs_with_success(): void
    {
        $activeDursCount = Dur::where('status', Dur::STATUS_ACTIVE)->count();

        $response = $this->json('GET', self::TEST_URL_DUR_V2, [], $this->headerNonAdmin);
        $response->assertJsonStructure([
            'current_page',
            'data',
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
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertEquals(
            $activeDursCount,
            count($response->decodeResponseJson()['data'])
        );
    }

    /**
     * Get All DURs for a given team with success
     *
     * @return void
     */
    public function test_get_all_team_durs_with_success(): void
    {
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamName = 'Team Test ' . fake()->regexify('[A-Z]{5}[0-4]{1}');

        $teamId1 = $this->createTeam([$this->nonAdminUser['id']], [$notificationID]);

        //create a 2nd team
        $teamId2 = $this->createTeam([$this->nonAdmin2User['id']], [$notificationID]);

        $specificTime = Carbon::parse('2023-01-01 00:00:00');
        Carbon::setTestNow($specificTime);

        $initialActiveDurCount = Dur::where('status', 'ACTIVE')->count();
        $initialDurCount = Dur::all()->count();
        // create dur
        $responseCreateDur = $this->json(
            'POST',
            $this->team_durs_url($teamId1),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ACTIVE',
            ],
            $this->headerNonAdmin,
        );
        $responseCreateDur->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        //create a 2nd active one
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);

        $responseCreateDur2 = $this->json(
            'POST',
            $this->team_durs_url($teamId1),
            [
                'project_title' => 'AB',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ACTIVE',
            ],
            $this->headerNonAdmin,
        );
        $responseCreateDur2->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        //create a 3nd one which is draft
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);

        $responseCreateDur3 = $this->json(
            'POST',
            $this->team_durs_url($teamId1),
            [
                'project_title' => 'AB',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $this->headerNonAdmin,
        );
        $responseCreateDur3->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        //create a 4th one which is draft and owned by the 2nd team
        $specificTime = Carbon::parse('2023-03-01 00:00:00');
        Carbon::setTestNow($specificTime);

        $responseCreateDur4 = $this->json(
            'POST',
            $this->team_durs_url($teamId2),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $this->headerNonAdmin2,
        );
        $responseCreateDur4->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        //create a 5th one which is owned by the 2nd team
        $specificTime = Carbon::parse('2023-03-01 00:00:00');
        Carbon::setTestNow($specificTime);

        $responseCreateDur4 = $this->json(
            'POST',
            $this->team_durs_url($teamId2),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ACTIVE',
            ],
            $this->headerNonAdmin2,
        );
        $responseCreateDur4->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        $response = $this->json(
            'GET',
            self::TEST_URL_DUR_V2,
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount($initialActiveDurCount + 3, $response['data']);
        $response->assertJsonStructure([
            'current_page',
            'data',
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

        /*
        * Test filtering by (active) dur project_title being ABC and AB
        */

        $response = $this->json(
            'GET',
            self::TEST_URL_DUR_V2 .
            '?project_title=ABC',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(2, $response['data']);

        $response = $this->json(
            'GET',
            self::TEST_URL_DUR_V2 .
            '?project_title=AB',
            [],
            $this->header
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(3, $response['data']);


        /*
        * Test filtering by (team) dur project_title being ABC and AB
        */

        $response = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/active' .
            '?project_title=ABC',
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(1, $response['data']);

        $response = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/active' .
            '?project_title=AB',
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(2, $response['data']);

        $response = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/draft' .
            '?project_title=ABC',
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(0, $response['data']);

        $response = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/draft' .
            '?project_title=AB',
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertCount(1, $response['data']);


        //create an archived dur from team1
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);

        $responseCreateDurArchived = $this->json(
            'POST',
            $this->team_durs_url($teamId1),
            [
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ARCHIVED',
            ],
            $this->headerNonAdmin,
        );
        $responseCreateDurArchived->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        /*
        * use the endpoint /api/v2/teams/{teamId}/dur/count to find unique values of the field 'status'
        */
        $responseCount = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/count/status',
            [],
            $this->headerNonAdmin
        );
        $responseCount->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $countActive = $responseCount['data']['ACTIVE'];
        $countDraft = $responseCount['data']['DRAFT'];
        $countArchived = $responseCount['data']['ARCHIVED'];

        $this->assertEquals(2, $countActive);
        $this->assertEquals(1, $countDraft);
        $this->assertEquals(1, $countArchived);

        // get active durs in this team
        $responseActiveDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/active',
            [],
            $this->headerNonAdmin
        );

        $responseActiveDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(2, $responseActiveDur['data']);

        // get draft durs in this team
        $responseDraftDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/draft',
            [],
            $this->headerNonAdmin
        );
        $responseDraftDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(1, $responseDraftDur['data']);

        // get archived durs in this team
        $responseArchivedDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/archived',
            [],
            $this->headerNonAdmin
        );
        $responseArchivedDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(1, $responseArchivedDur['data']);


        /*
        * nonAdmin2 is not in this team, so all team urls should fail
        */
        $responseCount = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/count/status',
            [],
            $this->headerNonAdmin2
        );
        $responseCount->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        // get active durs for all teams through public url
        $responseActiveDur = $this->json(
            'GET',
            self::TEST_URL_DUR_V2,
            [],
            $this->headerNonAdmin2
        );
        $responseActiveDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // (fail to) get active durs in this team
        $responseActiveDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/active',
            [],
            $this->headerNonAdmin2
        );
        $responseActiveDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        // (fail to) get draft datsets in this team
        $responseDraftDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/draft',
            [],
            $this->headerNonAdmin2
        );
        $responseDraftDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        // (fail to) get archived datsets in this team
        $responseArchivedDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/archived',
            [],
            $this->headerNonAdmin2
        );
        $responseArchivedDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));


        /*
        * fail if a bad direction has been given for sorting
        */
        $response = $this->json(
            'GET',
            $this->team_durs_url($teamId1) .
            '?sort=created:blah',
            [],
            $this->headerNonAdmin
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));

        // test deletion with various permissions combinations
        for ($i = $initialDurCount + 1; $i <= $initialDurCount + 5; $i++) {
            if ($i !== $initialDurCount + 4 && $i !== $initialDurCount + 5) {
                // delete dataset
                $responseDeleteDur = $this->json(
                    'DELETE',
                    $this->team_durs_url($teamId1) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin
                );
                $responseDeleteDur->assertJsonStructure([
                    'message'
                ]);

                $responseDeleteDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
            } else {
                // attempt to
                // 1) delete with incorrect team (1), and incorrect user (1)
                // 2) delete with correct team (2) but incorrect user (1)
                // 3) delete with incorrect team (1) but correct user (2)
                //
                // then complete the deletion with team 2 and user 2
                $responseDeleteDur = $this->json(
                    'DELETE',
                    $this->team_durs_url($teamId1) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin
                );
                $responseDeleteDur->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

                $responseDeleteDur = $this->json(
                    'DELETE',
                    $this->team_durs_url($teamId2) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin
                );
                $responseDeleteDur->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

                $responseDeleteDur = $this->json(
                    'DELETE',
                    $this->team_durs_url($teamId1) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin2
                );
                $responseDeleteDur->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

                $responseDeleteDur = $this->json(
                    'DELETE',
                    $this->team_durs_url($teamId2) .
                    '/' . $i,
                    [],
                    $this->headerNonAdmin2
                );
                $responseDeleteDur->assertJsonStructure([
                    'message'
                ]);
                $responseDeleteDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
            }
        }
    }

    /**
     * App Get All DURs for a given team with success
     *
     * @return void
     */
    public function test_app_can_get_all_team_durs_with_success(): void
    {
        $initialActiveDurCount = Dur::where('status', 'ACTIVE')->count();

        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId1 = $this->createTeam([$this->nonAdminUser['id']], [$notificationID]);
        $appHeader1 = $this->createApp($teamId1, $this->nonAdminUser['id']);

        //create a 2nd team
        $teamId2 = $this->createTeam([$this->nonAdmin2User['id']], [$notificationID]);
        $appHeader2 = $this->createApp($teamId2, $this->nonAdmin2User['id']);

        // create dur
        $responseCreateDur = $this->json(
            'POST',
            $this->team_durs_url($teamId1),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ACTIVE',
            ],
            $appHeader1,
        );
        $responseCreateDur->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $durId1 = $responseCreateDur['data'];

        $responseCreateDur2 = $this->json(
            'POST',
            $this->team_durs_url($teamId2),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $appHeader2,
        );
        $responseCreateDur2->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $durId2 = $responseCreateDur2['data'];

        $response = $this->json(
            'GET',
            self::TEST_URL_DUR_V2,
            [],
            $appHeader1
        );
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount($initialActiveDurCount + 1, $response['data']);
        $response->assertJsonStructure([
            'current_page',
            'data',
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

        //create an archived dur from team1
        $specificTime = Carbon::parse('2023-02-01 00:00:00');
        Carbon::setTestNow($specificTime);

        $responseCreateDurArchived = $this->json(
            'POST',
            $this->team_durs_url($teamId1),
            [
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ARCHIVED',
            ],
            $appHeader1,
        );
        $responseCreateDurArchived->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        // get archived durs in this team
        $responseArchivedDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/archived',
            [],
            $appHeader1
        );
        $responseArchivedDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $this->assertCount(1, $responseArchivedDur['data']);


        /*
        * nonAdmin2 is not in this team, so all team urls should fail
        */
        $responseCount = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/count/status',
            [],
            $appHeader2
        );
        $responseCount->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        // (fail to) get active durs in this team
        $responseActiveDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1),
            [],
            $appHeader2
        );
        $responseActiveDur->assertStatus(Config::get('statuscodes.STATUS_SERVER_ERROR.code'));

        // (fail to) get archived datsets in this team
        $responseArchivedDur = $this->json(
            'GET',
            $this->team_durs_url($teamId1) . '/status/archived',
            [],
            $this->headerNonAdmin2
        );
        $responseArchivedDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        // test deletion with various permissions combinations
        $responseDeleteDur = $this->json(
            'DELETE',
            $this->team_durs_url($teamId1) .
            '/' . $durId1,
            [],
            $appHeader2
        );
        $responseDeleteDur->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDur->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        $responseDeleteDur = $this->json(
            'DELETE',
            $this->team_durs_url($teamId1) .
            '/' . $durId1,
            [],
            $appHeader1
        );
        $responseDeleteDur->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDur->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

    }

    /**
     * Get Dataset by Id with success
     *
     * @return void
     */
    public function test_get_one_dur_by_id(): void
    {
        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

        // Create a second team for testing
        $teamId2 = $this->createTeam([], [$notificationID]);

        // create active dataset
        $responseCreateActiveDur = $this->json(
            'POST',
            $this->team_durs_url($teamId),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ACTIVE',
            ],
            $this->header,
        );

        $responseCreateActiveDur->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreateActiveDur = $responseCreateActiveDur->decodeResponseJson();
        $activeDurId = $contentCreateActiveDur['data'];

        // get one active dataset via V2 endpoint
        $responseGetOneActive = $this->json(
            'GET',
            self::TEST_URL_DUR_V2 . '/' . $activeDurId,
            [],
            $this->header
        );

        $responseGetOneActive->assertJsonStructure([
            'message',
            'data' => self::EXPECTED_DUR_RESPONSE_DATA,
        ]);
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $responseGetAll = $this->json('GET', $this->team_durs_url($teamId), [], $this->header);

        // get one active DUR via V2 teams endpoint
        $responseGetOneActive = $this->json('GET', $this->team_durs_url($teamId) . '/' . $activeDurId, [], $this->header);

        $responseGetOneActive->assertJsonStructure([
            'message',
            'data' => self::EXPECTED_DUR_RESPONSE_DATA,
        ]);
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // try and fail get one active dataset via V2 teams endpoint with wrong team id
        $responseGetOneActive = $this->json(
            'GET',
            $this->team_durs_url($teamId2) . '/' . $activeDurId,
            [],
            $this->header
        );
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));

        // delete active DUR
        $responseDeleteActive = $this->json(
            'DELETE',
            $this->team_durs_url($teamId) . '/' . $activeDurId,
            [],
            $this->header
        );
        $responseDeleteActive->assertJsonStructure([
            'message'
        ]);
        $responseDeleteActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // create draft DUR
        $responseCreateDraft = $this->json(
            'POST',
            $this->team_durs_url($teamId),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $this->header,
        );

        $responseCreateDraft->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreateDraft = $responseCreateDraft->decodeResponseJson();
        $draftDurId = $contentCreateDraft['data'];

        // fail to get draft DUR via V2 endpoint because only active are returned
        $responseGetOneDraftV2 = $this->json(
            'GET',
            self::TEST_URL_DUR_V2 . '/' . $draftDurId,
            [],
            $this->header
        );
        $responseGetOneDraftV2->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));
        $responseGetOneDraftV2->assertJson(['message' => 'Not Found']);

        // get draft DUR via V2 team endpoint
        $responseGetOneDraftV2 = $this->json(
            'GET',
            $this->team_durs_url($teamId) . '/' . $draftDurId,
            [],
            $this->header
        );
        $responseGetOneDraftV2->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // delete draft DUR
        $responseDeleteDraft = $this->json(
            'DELETE',
            $this->team_durs_url($teamId) . '/' . $draftDurId,
            [],
            $this->header
        );
        $responseDeleteDraft->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDraft->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // delete team
        $this->deleteTeam($teamId);

        // delete user
        $this->deleteUser($userId);
    }

    /**
     * App Get Dataset by Id with success
     *
     * @return void
     */
    public function test_app_can_get_one_dur_by_id(): void
    {
        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);
        $appHeader1 = $this->createApp($teamId, $this->nonAdminUser['id']);

        // create active dur
        $responseCreateActiveDur = $this->json(
            'POST',
            $this->team_durs_url($teamId),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'ACTIVE',
            ],
            $appHeader1,
        );

        $responseCreateActiveDur->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreateActiveDur = $responseCreateActiveDur->decodeResponseJson();
        $activeDurId = $contentCreateActiveDur['data'];

        // get one active dataset via V2 endpoint
        $responseGetOneActive = $this->json(
            'GET',
            self::TEST_URL_DUR_V2 . '/' . $activeDurId,
            [],
            $this->header
        );

        $responseGetOneActive->assertJsonStructure([
            'message',
            'data' => self::EXPECTED_DUR_RESPONSE_DATA,
        ]);
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        $responseGetAll = $this->json(
            'GET',
            $this->team_durs_url($teamId),
            [],
            $appHeader1
        );

        // get one active DUR via V2 teams endpoint
        $responseGetOneActive = $this->json(
            'GET',
            $this->team_durs_url($teamId) . '/' . $activeDurId,
            [],
            $appHeader1
        );

        $responseGetOneActive->assertJsonStructure([
            'message',
            'data' => self::EXPECTED_DUR_RESPONSE_DATA,
        ]);
        $responseGetOneActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // try and fail to deleted one active dur via V2 teams endpoint with wrong header
        $responseDELETEOneActive = $this->json(
            'DELETE',
            $this->team_durs_url($teamId) . '/' . $activeDurId,
            [],
            $this->headerNonAdmin2
        );
        $responseDELETEOneActive->assertStatus(Config::get('statuscodes.STATUS_UNAUTHORIZED.code'));

        // delete active DUR
        $responseDeleteActive = $this->json(
            'DELETE',
            $this->team_durs_url($teamId) . '/' . $activeDurId,
            [],
            $appHeader1
        );
        $responseDeleteActive->assertJsonStructure([
            'message'
        ]);
        $responseDeleteActive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
    }


    /**
     * Create/archive/unarchive DUR with success
     *
     * @return void
     */
    public function test_create_archive_update_delete_dur_with_success(): void
    {

        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

        // create DUR
        $labelDataset = 'label dataset ' . fake()->regexify('[A-Z]{5}[0-4]{1}');
        $responseCreate = $this->json(
            'POST',
            $this->team_durs_url($teamId),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $this->header,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreate = $responseCreate->decodeResponseJson();
        $durId = $contentCreate['data'];

        // archive DUR
        $responseArchive = $this->json(
            'PATCH',
            $this->team_durs_url($teamId) . '/' . $durId,
            [
                'status' => Dur::STATUS_ARCHIVED,
            ],
            $this->header
        );
        $responseArchive->assertJsonStructure([
            'message'
        ]);
        $responseArchive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // unarchive DUR
        $responseUnarchive = $this->json(
            'PATCH',
            $this->team_durs_url($teamId) . '/' . $durId,
            [
                'status' => Dur::STATUS_ACTIVE,
            ],
            $this->header
        );
        $responseUnarchive->assertJsonStructure([
            'message'
        ]);
        $responseUnarchive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // update DUR
        $responseUpdate = $this->json(
            'PUT',
            $this->team_durs_url($teamId) . '/' . $durId,
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $this->header,
        );
        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // (fail to) check status has updated correctly
        $responseGet = $this->json(
            'GET',
            self::TEST_URL_DUR_V2 . '/' . $durId,
            [],
            $this->header,
        );
        $responseGet->assertStatus(Config::get('statuscodes.STATUS_NOT_FOUND.code'));

        // check status has updated correctly
        $responseGet = $this->json(
            'GET',
            $this->team_durs_url($teamId) . '/' . $durId,
            [],
            $this->header,
        );
        $contentGet = $responseGet->decodeResponseJson();
        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertEquals(Dur::STATUS_DRAFT, $contentGet['data']['status']);

        // delete DUR
        $responseDelete = $this->json(
            'DELETE',
            $this->team_durs_url($teamId) . '/' . $durId,
            [],
            $this->header
        );
        $responseDelete->assertJsonStructure([
            'message'
        ]);
        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // delete team
        $this->deleteTeam($teamId);

        // delete user
        $this->deleteUser($userId);
    }

    /**
     * App Create/archive/unarchive DUR with success
     *
     * @return void
     */
    public function test_app_can_create_archive_update_delete_dur_with_success(): void
    {
        // create team
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);
        $appHeader = $this->createApp($teamId);

        // create user
        $userId = $this->createUser();

        // create DUR
        $responseCreate = $this->json(
            'POST',
            $this->team_durs_url($teamId),
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $appHeader,
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));
        $contentCreate = $responseCreate->decodeResponseJson();
        $durId = $contentCreate['data'];

        // archive DUR
        $responseArchive = $this->json(
            'PATCH',
            $this->team_durs_url($teamId) . '/' . $durId,
            [
                'status' => Dur::STATUS_ARCHIVED,
            ],
            $appHeader
        );
        $responseArchive->assertJsonStructure([
            'message'
        ]);
        $responseArchive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // unarchive DUR
        $responseUnarchive = $this->json(
            'PATCH',
            $this->team_durs_url($teamId) . '/' . $durId,
            [
                'status' => Dur::STATUS_ACTIVE,
            ],
            $appHeader
        );
        $responseUnarchive->assertJsonStructure([
            'message'
        ]);
        $responseUnarchive->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // update DUR
        $responseUpdate = $this->json(
            'PUT',
            $this->team_durs_url($teamId) . '/' . $durId,
            [
                'project_title' => 'ABC',
                'datasets' => $this->generateDatasets(),
                'publications' => $this->generatePublications(),
                'keywords' => $this->generateKeywords(),
                'tools' => $this->generateTools(),
                'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
                'latest_approval_date' => '2017-09-12T01:00:00',
                'organisation_sector' => 'academia',
                'status' => 'DRAFT',
            ],
            $appHeader
        );
        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // check status has updated correctly
        $responseGet = $this->json(
            'GET',
            $this->team_durs_url($teamId) . '/' . $durId,
            [],
            $appHeader,
        );
        $contentGet = $responseGet->decodeResponseJson();
        $responseGet->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertEquals(Dur::STATUS_DRAFT, $contentGet['data']['status']);

        // delete DUR
        $responseDelete = $this->json(
            'DELETE',
            $this->team_durs_url($teamId) . '/' . $durId,
            [],
            $appHeader
        );
        $responseDelete->assertJsonStructure([
            'message'
        ]);
        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        // delete team
        $this->deleteTeam($teamId);

        // delete user
        $this->deleteUser($userId);
    }

    public function test_download_dur_table_with_success_v2(): void
    {
        // Profiler middleware can't handle with streamed response,
        // but as it's a download, its implied that it may take a
        // bit longer, therefore we can safely ignore this for
        // profiling.
        Config::set('profiling.profiler_active', false);

        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        // create user
        $userId = $this->createUser();

        // create dur
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'publications' => $this->generatePublications(),
            'keywords' => $this->generateKeywords(),
            'tools' => $this->generateTools(),
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'academia',
            'status' => 'ACTIVE',
        ];

        $response = $this->json(
            'POST',
            $this->team_durs_url($teamId),
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
            self::TEST_URL_DUR_V2 . '/export',
            [],
            $this->header,
        );

        $content = $responseDownload->streamedContent();
        $this->assertMatchesRegularExpression('/Non-Gateway Datasets/', $content);
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
        $response = $this->get(self::TEST_URL_DUR_V2 . '/template');

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
        $response = $this->get(self::TEST_URL_DUR_V2 . '/template');

        // Assert the file is not found
        $response->assertStatus(404);
        $response->assertJson(['error' => 'File not found.']);
    }

    public function test_the_application_can_search_on_project_title(): void
    {
        // First create a notification to be used by the new team
        $notificationID = $this->createNotification();

        // Create the new team
        $teamId = $this->createTeam([], [$notificationID]);

        $mockData = [
            'project_title' => '12345-67890',
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
            $this->team_durs_url($teamId),
            $mockData,
            $this->header
        );

        $response->assertStatus(201);

        $response = $this->json(
            'GET',
            $this->team_durs_url($teamId) . '/status/active?project_title=' . $mockData['project_title'],
            [],
            $this->header
        );

        $response->assertStatus(200);
        $content = $response->decodeResponseJson()['data'];
        $this->assertTrue($content[0]['project_title'] === $mockData['project_title']);
        $this->assertTrue(count($content) === 1);

        // delete team
        $this->deleteTeam($teamId);
    }

    private function team_durs_url(int $teamId)
    {
        return 'api/v2/teams/' . $teamId . '/dur';
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
