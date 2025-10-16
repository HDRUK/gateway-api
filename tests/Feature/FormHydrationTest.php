<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;
use App\Http\Enums\TeamMemberOf;
use MetadataManagementController as MMC;

class FormHydrationTest extends TestCase
{
    use Authorization;

    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];
    protected $metadata;
    protected $currentUser;

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        DatasetVersion::flushEventListeners();

        $jsonFile = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v2p0_dataset_min.json', 0, null);
        $json = json_decode($jsonFile, true);
        $this->metadata = $json;

        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->currentUser = $this->getUserFromJwt($jwt);
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
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

        // Create the new team
        $responseCreateTeam = $this->json(
            'POST',
            'api/v1/teams',
            [
                'name' => 'Form hydration test team',
                'enabled' => 1,
                'allows_messaging' => 1,
                'workflow_enabled' => 1,
                'access_requests_management' => 1,
                'uses_5_safes' => 1,
                'is_admin' => 1,
                'member_of' => TeamMemberOf::OTHER,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => '2023-04-06 15:44:41',
                'is_question_bank' => 1,
                'notifications' => [],
                'users' => [],
                'url' => 'https://fakeimg.pl/350x200/ff0000/000',
                'introduction' => fake()->sentence(),
                'dar_modal_content' => fake()->sentence(),
                'service' => 'https://service.local/test',
            ],
            $this->header
        );

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);

        $content = $responseCreateTeam->decodeResponseJson();
        $teamId = $content['data'];

        // Create a new user
        $responseUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'FormHydration',
                'lastname' => 'Test',
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
        $responseUser->assertStatus(201);
        $userId = $responseUser->decodeResponseJson()['data'];

        // Create a new dataset with the above team and user
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

        $response = $this->get('api/v1/form_hydration?team_id=' . $teamId);
        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'data' => [
                    'schema_fields',
                    'validation',
                    'defaultValues'
                ]
            ]);

        $defaultValues = $response->decodeResponseJson()['data']['defaultValues'];
        $this->assertEquals($defaultValues['Name of Data Custodian'], 'Form hydration test team');
        $this->assertEquals($defaultValues['Jurisdiction'], ['UK']);
        $this->assertNull($defaultValues['Organisation Logo']);
        $this->assertIsArray($defaultValues['Data use limitation']);
    }

}
