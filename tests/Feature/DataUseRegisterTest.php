<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\DataUseRegister;
use App\Models\TeamHasUser;
use App\Models\Dataset;
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
                    'dataset_id',
                    'enabled',
                    'user_id',
                    'ro_crate',
                    'organization_name',
                    'project_title',
                    'lay_summary',
                    'public_benefit_statement',
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
    public function test_get_data_use_register_by_id_with_success(): void
    {
        # Create new DataUseRegister item
        $teamHasUser = TeamHasUser::all()->random();
        $dataset = Dataset::all()->random();

        $randomString = fake()->words(fake()->randomDigitNot(0), true);
        $shortRandomString = fake()->words(fake()->numberBetween(1, 4), true);
        $randomWord = fake()->word();
        $fakeROCrate = json_decode('{
            "@context": "https://example.com/context",
            "@graph": [
            ]
        }', true);

        $mockData = [
            "dataset_id" => (int) $dataset->id,
            "enabled" => fake()->boolean(),
            "user_id" => (int) $teamHasUser->user_id,
            "ro_crate" => $fakeROCrate,
            "organization_name" => $randomString,
            "project_title" => $randomString,
            "lay_summary" => $randomString,
            "public_benefit_statement" => $randomString,
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );

        $response->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $response->decodeResponseJson();
        $this->assertEquals($content['message'], 
            Config::get('statuscodes.STATUS_CREATED.message'));

        # Get DataUseRegister item
        $response = $this->json('GET', self::TEST_URL . '/'. $content['data'], [], $this->header);

        $content = $response->decodeResponseJson();
        $this->assertCount(1, $content['data']);

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'dataset_id',
                    'enabled',
                    'user_id',
                    'ro_crate',
                    'organization_name',
                    'project_title',
                    'lay_summary',
                    'public_benefit_statement',                ]
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
        $dataset = Dataset::all()->random();

        $randomString = fake()->words(fake()->randomDigitNot(0), true);
        $shortRandomString = fake()->words(fake()->numberBetween(1, 4), true);
        $randomWord = fake()->word();
        $fakeROCrate = json_decode('{
            "@context": "https://example.com/context",
            "@graph": [
            ]
        }', true);

        $mockData = [
            "dataset_id" => (int) $dataset->id,
            "enabled" => fake()->boolean(),
            "user_id" => (int) $teamHasUser->user_id,
            "ro_crate" => $fakeROCrate,
            "organization_name" => $randomString,
            "project_title" => $randomString,
            "lay_summary" => $randomString,
            "public_benefit_statement" => $randomString,
        ];

        $response = $this->json(
            'POST',
            self::TEST_URL,
            $mockData,
            $this->header
        );

        $countAfter = DataUseRegister::withTrashed()->count();
        $countNewRow = $countAfter - $countBefore;
        $this->assertTrue((bool) $countNewRow, 'Response was successful');
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
        $dataset = Dataset::all()->random();
        $randomString = fake()->words(fake()->numberBetween(1, 10), true);
        $randomWord = fake()->word();
        $fakeROCrate = json_decode('{
            "@context": "https://example.com/context",
            "@graph": [
            ]
        }', true);

        // create new data_use_register
        $mockDataIns = [
            "dataset_id" => (int) $dataset->id,
            "enabled" => fake()->boolean(),
            "user_id" => $teamHasUser->user_id,
            "ro_crate" => $fakeROCrate,
            "organization_name" => $randomString,
            "project_title" => $randomString,
            "lay_summary" => $randomString,
            "public_benefit_statement" => $randomString,
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
        $dataset2 = Dataset::all()->random();
        $randomString2 = fake()->words(fake()->numberBetween(1, 10), true);
        $randomWord2 = fake()->word();
        $fakeROCrate2 = json_decode('{
            "@context": "https://example2.com/context",
            "@graph": [
            ]
        }', true);

        // update data_use_register
        $mockDataUpdate = [
            "dataset_id" => (int) $dataset2->id,
            "enabled" => fake()->boolean(),
            "user_id" => $teamHasUser2->user_id,
            "ro_crate" => $fakeROCrate2,
            "organization_name" => $randomString2,
            "project_title" => $randomString2,
            "lay_summary" => $randomString2,
            "public_benefit_statement" => $randomString2,
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $idIns,
            $mockDataUpdate,
            $this->header
        );
        $responseUpdate->assertStatus(202);


        $this->assertTrue($mockDataUpdate['dataset_id'] === $responseUpdate['data']['dataset_id']);
        $this->assertTrue($mockDataUpdate['enabled'] === $responseUpdate['data']['enabled']);
        $this->assertTrue($mockDataUpdate['user_id'] === $responseUpdate['data']['user_id']);
        $this->assertTrue(json_encode($mockDataUpdate['ro_crate']) === $responseUpdate['data']['ro_crate'], true);
        $this->assertTrue($mockDataUpdate['organization_name'] === $responseUpdate['data']['organization_name']);
        $this->assertTrue($mockDataUpdate['project_title'] === $responseUpdate['data']['project_title']);
        $this->assertTrue($mockDataUpdate['lay_summary'] === $responseUpdate['data']['lay_summary']);
        $this->assertTrue($mockDataUpdate['public_benefit_statement'] === $responseUpdate['data']['public_benefit_statement']);
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
        $dataset = Dataset::all()->random();
        $randomString = fake()->words(fake()->randomDigitNot(0), true);
        $shortRandomString = fake()->words(fake()->numberBetween(1, 4), true);
        $randomWord = fake()->word();
        $fakeROCrate = json_decode('{
            "@context": "https://example.com/context",
            "@graph": [
            ]
        }', true);

        // create new data_use_register
        $mockDataIns = [
            "dataset_id" => (int) $dataset->id,
            "enabled" => fake()->boolean(),
            "user_id" => $teamHasUser->user_id,
            "ro_crate" => $fakeROCrate,
            "organization_name" => $randomString,
            "project_title" => $randomString,
            "lay_summary" => $randomString,
            "public_benefit_statement" => $randomString,
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
        $this->assertTrue((bool) ($countAfter - $countBefore), 'Response was successful');

        // delete data_use_register
        $response = $this->json('DELETE', self::TEST_URL . '/' . $idIns, [], $this->header);
        $response->assertStatus(200);
        $countTrasherAfter = DataUseRegister::onlyTrashed()->count();
        $this->assertTrue((bool) ($countTrasherAfter - $countTrashedBefore), 'Response was successful');
    }
}
