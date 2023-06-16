<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DataUseRegister;
use App\Models\TeamHasUser;
use Tests\Traits\Authorization;
// use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DataUseRegisterTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/data_use_registers';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
    }

    /**
     * Get All DataUseRegisters with success
     * 
     * @return void
     */
    public function test_get_all_data_use_registers_with_success(): void
    {
        $countDataUseRegister = DataUseRegister::count();
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $this->assertCount($countDataUseRegister, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'counter',
                    'keywords',
                    'dataset_ids',
                    'gateway_dataset_ids',
                    'non_gateway_dataset_ids',
                    'gateway_applicants',
                    'non_gateway_applicants',
                    'funders_and_sponsors',
                    'other_approval_committees',
                    'gateway_output_tools',
                    'gateway_output_papers',
                    'non_gateway_outputs',
                    'project_title',
                    'project_id_text',
                    'organisation_name',
                    'organisation_sector',
                    'lay_summary',
                    'latest_approval_date',
                    'enabled',
                    'team_id',
                    'user_id',
                    'last_activity',
                    'manual_upload',
                    'rejection_reason',
                    'created_at',
                    'updated_at',
                    'deleted_at',
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
     * Get All DataUseRegisters with no success
     * 
     * @return void
     */
    public function test_get_all_data_use_registers_and_generate_exception(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], []);
        $response->assertStatus(401);
    }

    /**
     * Get DataUseRegister by Id with success
     * 
     * @return void
     */
    public function test_get_data_use_register_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'counter',
                    'keywords',
                    'dataset_ids',
                    'gateway_dataset_ids',
                    'non_gateway_dataset_ids',
                    'gateway_applicants',
                    'non_gateway_applicants',
                    'funders_and_sponsors',
                    'other_approval_committees',
                    'gateway_output_tools',
                    'gateway_output_papers',
                    'non_gateway_outputs',
                    'project_title',
                    'project_id_text',
                    'organisation_name',
                    'organisation_sector',
                    'lay_summary',
                    'latest_approval_date',
                    'enabled',
                    'team_id',
                    'user_id',
                    'last_activity',
                    'manual_upload',
                    'rejection_reason',
                    'created_at',
                    'updated_at',
                    'deleted_at',
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
    public function test_add_new_data_use_register_with_success(): void
    {
        $countBefore = DataUseRegister::withTrashed()->count();

        $teamHasUser = TeamHasUser::all()->random();

        $randomString = fake()->words(fake()->randomDigit(), true);
        $shortRandomString = fake()->words(fake()->numberBetween(1, 4), true);
        $randomWord = fake()->word();

        $mockData = [
            "counter" => fake()->randomNumber(4, false),
            "keywords" => [$randomWord],
            "dataset_ids" => [$randomWord],
            "gateway_dataset_ids" => [$randomWord],
            "non_gateway_dataset_ids" => [$randomWord],
            "gateway_applicants" => [$randomWord],
            "non_gateway_applicants" => [$randomWord],
            "funders_and_sponsors" => [$randomWord],
            "other_approval_committees" => [$randomWord],
            "gateway_output_tools" => [$randomWord],
            "gateway_output_papers" => [$randomWord],
            "non_gateway_outputs" => [$randomWord],
            "project_title" => $randomString,
            "project_id_text" => $shortRandomString,
            "organisation_name" => $randomString,
            "organisation_sector" => $randomString,
            "lay_summary" => $randomString,
            "latest_approval_date" => "2023-07-06 10:00:00",
            "enabled" => fake()->boolean(),
            "team_id" => (int) $teamHasUser->team_id,
            "user_id" => (int) $teamHasUser->user_id,
            "last_activity" => "2023-07-06 10:00:00",
            "manual_upload" => fake()->boolean(),
            "rejection_reason" => $randomString,
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );

        $countAfter = DataUseRegister::withTrashed()->count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
    }

    /**
     * Update DataUseRegister with success by id
     *
     * @return void
     */
    public function test_update_data_use_register_with_success(): void 
    {
        $teamHasUser = TeamHasUser::all()->random();
        $randomString = fake()->words(fake()->numberBetween(1, 10), true);
        $shortRandomString = fake()->words(fake()->numberBetween(1, 4), true);
        $randomWord = fake()->word();

        // create new data_use_register
        $mockDataIns = [
            "counter" => fake()->randomNumber(4, false),
            "keywords" => [$randomWord],
            "dataset_ids" => [$randomWord],
            "gateway_dataset_ids" => [$randomWord],
            "non_gateway_dataset_ids" => [$randomWord],
            "gateway_applicants" => [$randomWord],
            "non_gateway_applicants" => [$randomWord],
            "funders_and_sponsors" => [$randomWord],
            "other_approval_committees" => [$randomWord],
            "gateway_output_tools" => [$randomWord],
            "gateway_output_papers" => [$randomWord],
            "non_gateway_outputs" => [$randomWord],
            "project_title" => $randomString,
            "project_id_text" => $shortRandomString,
            "organisation_name" => $randomString,
            "organisation_sector" => $randomString,
            "lay_summary" => $randomString,
            "latest_approval_date" => "2023-07-06 10:00:00",
            "enabled" => fake()->boolean(),
            "team_id" => $teamHasUser->team_id,
            "user_id" => $teamHasUser->user_id,
            "last_activity" => "2023-07-06 10:00:00",
            "manual_upload" => fake()->boolean(),
            "rejection_reason" => $randomString,
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL,
            $mockDataIns,
            $this->header
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        $teamHasUser2 = TeamHasUser::all()->random();
        $randomString2 = fake()->words(fake()->numberBetween(1, 10), true);
        $shortRandomString2 = fake()->words(fake()->numberBetween(1, 4), true);
        $randomWord2 = fake()->word();

        // update data_use_register
        $mockDataUpdate = [
            "counter" => fake()->randomNumber(4, false),
            "keywords" => [$randomWord2],
            "dataset_ids" => [$randomWord2],
            "gateway_dataset_ids" => [$randomWord2],
            "non_gateway_dataset_ids" => [$randomWord2],
            "gateway_applicants" => [$randomWord2],
            "non_gateway_applicants" => [$randomWord2],
            "funders_and_sponsors" => [$randomWord2],
            "other_approval_committees" => [$randomWord2],
            "gateway_output_tools" => [$randomWord2],
            "gateway_output_papers" => [$randomWord2],
            "non_gateway_outputs" => [$randomWord2],
            "project_title" => $randomString2,
            "project_id_text" => $shortRandomString2,
            "organisation_name" => $randomString2,
            "organisation_sector" => $randomString2,
            "lay_summary" => $randomString2,
            "latest_approval_date" => "2023-07-06 10:00:00",
            "enabled" => fake()->boolean(),
            "team_id" => $teamHasUser2->team_id,
            "user_id" => $teamHasUser2->user_id,
            "last_activity" => "2023-07-06 10:00:00",
            "manual_upload" => fake()->boolean(),
            "rejection_reason" => $randomString2,
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $idIns,
            $mockDataUpdate,
            $this->header
        );
        $responseUpdate->assertStatus(202);

        $this->assertTrue($mockDataUpdate['counter'] === $responseUpdate['data']['counter']);
        $this->assertTrue($mockDataUpdate['keywords'] === $responseUpdate['data']['keywords']);
        $this->assertTrue($mockDataUpdate['dataset_ids'] === $responseUpdate['data']['dataset_ids']);
        $this->assertTrue($mockDataUpdate['gateway_dataset_ids'] === $responseUpdate['data']['gateway_dataset_ids']);
        $this->assertTrue($mockDataUpdate['non_gateway_dataset_ids'] === $responseUpdate['data']['non_gateway_dataset_ids']);
        $this->assertTrue($mockDataUpdate['gateway_applicants'] === $responseUpdate['data']['gateway_applicants']);
        $this->assertTrue($mockDataUpdate['non_gateway_applicants'] === $responseUpdate['data']['non_gateway_applicants']);
        $this->assertTrue($mockDataUpdate['funders_and_sponsors'] === $responseUpdate['data']['funders_and_sponsors']);
        $this->assertTrue($mockDataUpdate['other_approval_committees'] === $responseUpdate['data']['other_approval_committees']);
        $this->assertTrue($mockDataUpdate['gateway_output_tools'] === $responseUpdate['data']['gateway_output_tools']);
        $this->assertTrue($mockDataUpdate['gateway_output_papers'] === $responseUpdate['data']['gateway_output_papers']);
        $this->assertTrue($mockDataUpdate['non_gateway_outputs'] === $responseUpdate['data']['non_gateway_outputs']);
        $this->assertTrue($mockDataUpdate['project_title'] === $responseUpdate['data']['project_title']);
        $this->assertTrue($mockDataUpdate['project_id_text'] === $responseUpdate['data']['project_id_text']);
        $this->assertTrue($mockDataUpdate['organisation_name'] === $responseUpdate['data']['organisation_name']);
        $this->assertTrue($mockDataUpdate['organisation_sector'] === $responseUpdate['data']['organisation_sector']);
        $this->assertTrue($mockDataUpdate['lay_summary'] === $responseUpdate['data']['lay_summary']);
        $this->assertTrue(strtotime($mockDataUpdate['latest_approval_date']) === strtotime($responseUpdate['data']['latest_approval_date']));
        $this->assertTrue($mockDataUpdate['enabled'] === $responseUpdate['data']['enabled']);
        $this->assertTrue($mockDataUpdate['team_id'] === $responseUpdate['data']['team_id']);
        $this->assertTrue($mockDataUpdate['user_id'] === $responseUpdate['data']['user_id']);
        $this->assertTrue(strtotime($mockDataUpdate['last_activity']) === strtotime($responseUpdate['data']['last_activity']));
        $this->assertTrue((boolean) $mockDataUpdate['manual_upload'] === (boolean) $responseUpdate['data']['manual_upload']);
        $this->assertTrue($mockDataUpdate['rejection_reason'] === $responseUpdate['data']['rejection_reason']);
    }

    /**
     * SoftDelete DataUseRegister by Id with success
     *
     * @return void
     */
    public function test_soft_delete_data_use_register_with_success(): void
    {
        $countBefore = DataUseRegister::count();
        $countTrashedBefore = DataUseRegister::onlyTrashed()->count();

        $teamHasUser = TeamHasUser::all()->random();
        $randomString = fake()->words(fake()->randomDigit(), true);
        $shortRandomString = fake()->words(fake()->numberBetween(1, 4), true);
        $randomWord = fake()->word();

        // create new data_use_register
        $mockDataIns = [
            "counter" => fake()->randomNumber(4, false),
            "keywords" => [$randomWord],
            "dataset_ids" => [$randomWord],
            "gateway_dataset_ids" => [$randomWord],
            "non_gateway_dataset_ids" => [$randomWord],
            "gateway_applicants" => [$randomWord],
            "non_gateway_applicants" => [$randomWord],
            "funders_and_sponsors" => [$randomWord],
            "other_approval_committees" => [$randomWord],
            "gateway_output_tools" => [$randomWord],
            "gateway_output_papers" => [$randomWord],
            "non_gateway_outputs" => [$randomWord],
            "project_title" => $randomString,
            "project_id_text" => $shortRandomString,
            "organisation_name" => $randomString,
            "organisation_sector" => $randomString,
            "lay_summary" => $randomString,
            "latest_approval_date" => "2023-07-06 10:00:00",
            "enabled" => fake()->boolean(),
            "team_id" => $teamHasUser->team_id,
            "user_id" => $teamHasUser->user_id,
            "last_activity" => "2023-07-06 10:00:00",
            "manual_upload" => fake()->boolean(),
            "rejection_reason" => $randomString,
        ];
        $responseIns = $this->json(
            'POST',
            self::TEST_URL,
            $mockDataIns,
            $this->header
        );

        $responseIns->assertStatus(201);
        $idIns = (int) $responseIns['data'];

        $countAfter = DataUseRegister::count();
        $this->assertTrue((bool) ($countAfter - $countBefore), 'Response was successfully');

        // delete data_use_register
        $response = $this->json('DELETE', self::TEST_URL . '/' . $idIns, [], $this->header);
        $response->assertStatus(200);
        $countTrasherAfter = DataUseRegister::onlyTrashed()->count();
        $this->assertTrue((bool) ($countTrasherAfter - $countTrashedBefore), 'Response was successfully');
    }
}
