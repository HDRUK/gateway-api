<?php

namespace Tests\Feature;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MetadataManagementController as MMC;
use Config;

use App\Models\Dataset;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\TeamHasUserSeeder;
use Database\Seeders\KeywordSeeder;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\DatasetVersionSeeder;

use Tests\Traits\Authorization;

class FormHydrationTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

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
            TeamHasUserSeeder::class,
            KeywordSeeder::class,
            DatasetSeeder::class,
            DatasetVersionSeeder::class,
        ]);

        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v1_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);
        $this->metadata = $json;
    }

    public function test_form_hydration_schema(): void
    {
        $response = $this->get('api/v1/form_hydration/schema');

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
            'data' => [
                'schema_fields' => [
                    0 => [
                        'required',
                        'title',
                        'description',
                        'examples',
                        'is_list',
                        'is_optional',
                        'types'
                    ]
                ]
            ]
        ]);
    }

    public function test_form_hydration_schema_with_parameters(): void
    {
        $response = $this->get('api/v1/form_hydration/schema?model=HDRUK');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
            'data' => [
                'schema_fields' => [
                    0 => [
                        'required',
                        'title',
                        'description',
                        'examples',
                        'is_list',
                        'is_optional',
                        'types'
                    ]
                ]
            ]
        ]);

        $responseOldVersion = $this->get('api/v1/form_hydration/schema?model=HDRUK&version=2.1.2');
        $responseOldVersion->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
         ->assertJsonStructure([
            'data' => [
                'schema_fields' => [
                    0 => [
                        'required',
                        'title',
                        'description',
                        'examples',
                        'is_list',
                        'is_optional',
                        'types'
                    ]
                ]
            ]
        ]);

        $this->assertNotEquals($response, $responseOldVersion);


    }

    public function test_form_hydration_schema_will_fail(): void
    {
        $response = $this->get('api/v1/form_hydration/schema?model=blah');
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));

        $response = $this->get('api/v1/form_hydration/schema?version=9.9.9');
        $response->assertStatus(Config::get('statuscodes.STATUS_BAD_REQUEST.code'));
    }

    public function test_get_form_hydration_with_success(): void
    {
        $hydratedForm = [
            "schema_fields" => [
                0 => [
                    "title" => "Title",
                    "is_array_form" => false,
                    "description" => "Title of the dataset limited to 150 characters. It should provide a short description of the dataset and be unique across the gateway. If your title is not unique, please add a prefix with your organisation name or identifier to differentiate it from other datasets within the Gateway. Please avoid acronyms wherever possible. Good titles should summarise the content of the dataset and if relevant, the region the dataset covers.",
                    "location" => "summary.title",
                    "guidance" => "- The **title** should provide a short description of the dataset and be **unique** across the gateway.\\n- If your title is not unique, please **add a prefix with your organisation name or identifier** to differentiate it from other datasets within the Gateway.\\n- If the dataset is a **“linked dataset”**, please indicate this using the prefix **“Linked”**.\\n- Please **avoid acronyms** wherever possible.\\n- Good titles should summarise the content of the dataset and if relevant, **the region the dataset covers**.\\n- **Example**: North West London COVID-19 Patient Level Situation Report',",
                    "field" => [
                        "component" => "TextField",
                        "name" => "Title",
                        "placeholder" => "North West London COVID-19 Patient Level Situation Report",
                        "label" => "Title of the dataset limited to 150 characters. It should provide a short description of the dataset and be unique across the gateway. If your title is not unique, please add a prefix with your organisation name or identifier to differentiate it from other datasets within the Gateway. Please avoid acronyms wherever possible. Good titles should summarise the content of the dataset and if relevant, the region the dataset covers.",
                        "limit" => 150,
                        "required" => true,
                        "hidden" => false
                    ]
                ]
            ],
            "validation" => [
                0 => [
                    "title" => "Title",
                    "required" => true,
                    "type" => "string",
                    "min" => 2,
                    "max" => 150
                ]
            ]
        ];
        MMC::shouldReceive("getOnboardingFormHydrated")->andReturn($hydratedForm);
        MMC::shouldReceive("translateDataModelType")
            ->andReturnUsing(function (string $metadata) {
                return [
                    "traser_message" => "",
                    "wasTranslated" => true,
                    "metadata" => json_decode($metadata, true)["metadata"],
                    "statusCode" => "200",
                ];
            });
        MMC::shouldReceive("validateDataModelType")->andReturn(true);
        MMC::makePartial();

        $teamId = Team::all()->random()->id;
        $userId = User::all()->random()->id;
        $responseCreateDataset = $this->json(
            'POST',
            'api/v1/datasets',
            [
                'team_id' => $teamId,
                'user_id' => $userId,
                'metadata' => $this->metadata,
                'create_origin' => Dataset::ORIGIN_MANUAL,
                'status' => Dataset::STATUS_ACTIVE,
            ],
            $this->header,
        );

        // LS - Removed teamId as this is an iffy test at best, in terms of data
        // available. This test doesn't create everything it needs to ensure
        // successful outcome, thus relies entirely on seeded/migrated data
        // which has been completely hit and miss.
        $response = $this->get('api/v1/form_hydration');
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                "data" => [
                    "schema_fields",
                    "validation",
                    "defaultValues"
                ]
            ]);

    }

}
