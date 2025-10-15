<?php

namespace Tests\Feature;

use App\Models\Dur;
use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\Sector;
use App\Models\Dataset;
use App\Models\Keyword;
use App\Models\Permission;
use App\Models\Application;
use Tests\Traits\MockExternalApis;
use App\Models\ApplicationHasPermission;

class DurIntegrationDeprecatedTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/integrations/dur';

    protected $header = [];
    protected $integration;

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        $this->integration = Application::where('id', 1)->first();

        $perms = Permission::whereIn('name', [
            'dur.create',
            'dur.read',
            'dur.update',
            'dur.delete',
        ])->get();

        foreach ($perms as $perm) {
            // Use firstOrCreate ignoring the return as we only care that missing perms
            // of the above are added, rather than retrieving existing
            ApplicationHasPermission::firstOrCreate([
                'application_id' => $this->integration->id,
                'permission_id' => $perm->id,
            ]);
        }

        // Add Integration auth keys to the header generated in commonSetUp
        $this->header['x-application-id'] = $this->integration['app_id'];
        $this->header['x-client-id'] = $this->integration['client_id'];
    }

    /**
     * Get All DataUseRegisters with success
     *
     * @return void
     */
    public function test_get_all_integration_dur_with_success(): void
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
                    'team',
                    'user',
                    'applicant_id',
                    'applications',
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
     * Get DataUseRegister by Id with success
     *
     * @return void
     */
    public function test_get_integration_dur_by_id_with_success(): void
    {
        $durId = (int) Dur::all()->random()->id;
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
                    'publications',
                    'tools',
                    'datasets' => [
                        '*' => [
                            'id',
                            'shortTitle',
                        ]
                    ],
                    'keywords',
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
     * Create new DataUseRegister with success
     *
     * @return void
     */
    public function test_add_new_integration_dur_with_success(): void
    {
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => [$this->generateDatasets()],
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

        $countAfter = Dur::count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');

        // Check that the sector has been correctly mapped.
        $dur_index = $response->json()['data'];
        $this->assertEquals(
            Dur::where('id', $dur_index)->first()['sector_id'],
            Sector::where('name', 'Academia')->first()['id']
        );
    }

    /**
     * Update DataUseRegister with success by id
     *
     * @return void
     */
    public function test_update_integration_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => [$this->generateDatasets()],
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
            'datasets' => [$this->generateDatasets()],
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02', 'External Dataset 03'],
            'latest_approval_date' => '2017-09-12T01:00:00',
            'organisation_sector' => 'Commercial',
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
     * SoftDelete DataUseRegister by Id with success
     *
     * @return void
     */
    public function test_soft_delete_integration_dur_with_success(): void
    {
        // create dur
        $userId = (int) User::all()->random()->id;
        $teamId = (int) Team::all()->random()->id;
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => [$this->generateDatasets()],
            'keywords' => $this->generateKeywords(),
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

    private function generateKeywords()
    {
        $return = [];
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $return[] = Keyword::where(['enabled' => 1])->get()->random()->name;
        }

        return array_unique($return);
    }

    private function generateDatasets()
    {
        $return = [];

        $return['id'] = Dataset::all()->random()->id;
        $return['reason'] = htmlentities(implode(" ", fake()->paragraphs(5, false)), ENT_QUOTES | ENT_IGNORE, "UTF-8");
        $return['is_locked'] = fake()->randomElement([0, 1]);

        return $return;
    }
}
