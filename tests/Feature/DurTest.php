<?php

namespace Tests\Feature;

use App\Models\Dur;
use Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use App\Models\Dataset;
use App\Models\Keyword;
use Database\Seeders\DurSeeder;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\DatasetVersionSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DurTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    const TEST_URL = '/api/v1/dur';

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
            ApplicationSeeder::class,
            CollectionSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
            KeywordSeeder::class,
            DurSeeder::class,
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
                    'confidential_description',
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
                    'keywords',
                    'applications',
                    'team',
                    'user',
                    'application',
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
                    'confidential_description',
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
                    'keywords',
                    'applications',
                    'team',
                    'user',
                    'application',
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
        $countBefore = Dur::count();
        $mockData = [
            'datasets' => $this->generateDatasets(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
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
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
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

        // update
        $mockDataUpdate = [
            'datasets' => $this->generateDatasets(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01','External Dataset 02', 'External Dataset 03'],
            'latest_approval_date' => '2017-09-12T01:00:00',
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $durId,
            $mockDataUpdate,
            $this->header
        );
        $responseUpdate->assertStatus(200);
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
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
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

        // update
        $mockDataUpdate = [
            'datasets' => $this->generateDatasets(),
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02', 'External Dataset 03'],
            'latest_approval_date' => '2017-09-12T01:00:00',
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
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
        ];
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $durId,
            $mockDataEdit,
            $this->header
        );
        $responseEdit->assertStatus(200);
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
            'keywords' => $this->generateKeywords(),
            'user_id' => $userId,
            'team_id' => $teamId,
            'non_gateway_datasets' => ['External Dataset 01', 'External Dataset 02'],
            'latest_approval_date' => '2017-09-12T01:00:00',
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
        $iterations = rand(1, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $return[] = Dataset::all()->random()->id;
        }

        return array_unique($return);
    }
}
