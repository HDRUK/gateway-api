<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Dataset;
use App\Models\Permission;
use App\Models\Application;
use App\Models\DatasetVersion;
use Tests\Traits\Authorization;
use App\Http\Enums\TeamMemberOf;
use Database\Seeders\TeamSeeder;
use Database\Seeders\SectorSeeder;
use Tests\Traits\MockExternalApis;
use Database\Seeders\DatasetSeeder;
use Database\Seeders\ApplicationSeeder;
use Database\Seeders\MinimalUserSeeder;
use App\Models\ApplicationHasPermission;
use Database\Seeders\SpatialCoverageSeeder;

class DatasetIntegrationTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET = '/api/v1/integrations/datasets';
    public const TEST_URL_TEAM = 'api/v1/teams';
    public const TEST_URL_NOTIFICATION = 'api/v1/notifications';
    public const TEST_URL_USER = 'api/v1/users';

    private $metadata = null;
    private $integration = null;

    protected $header = [];


    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();

        Dataset::flushEventListeners();
        DatasetVersion::flushEventListeners();

        $this->seed([
            MinimalUserSeeder::class,
            DatasetSeeder::class,
            SectorSeeder::class,
            TeamSeeder::class,
            ApplicationSeeder::class,
            SpatialCoverageSeeder::class,
        ]);

        $this->integration = Application::where('id', 1)->first();

        $perms = Permission::whereIn('name', [
            'datasets.create',
            'datasets.read',
            'datasets.update',
            'datasets.delete',
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
        $this->header['x-application-id'] = $this->integration->app_id;
        $this->header['x-client-id'] = $this->integration->client_id;

        // Lengthy process, but a more consistent representation
        // of an incoming dataset
        $this->metadata = $this->getMetadata();
    }

    /**
     * Get All Datasets with success
     *
     * @return void
     */
    public function test_get_all_datasets_with_success(): void
    {
        // First create a dataset for the team who owns this integration
        $response = $this->json('POST', self::TEST_URL_DATASET, [
            'metadata' => $this->metadata,
        ], $this->header);

        $response->assertStatus(201);

        $response = $this->json('GET', self::TEST_URL_DATASET, [], $this->header);
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
        $response->assertStatus(200);
    }

    /**
     * Get Dataset by Id with success
     *
     * @return void
     */
    public function test_get_one_dataset_by_id_with_success(): void
    {

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

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
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

        // create dataset
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'metadata' => $this->metadata,
            ],
            $this->header,
        );

        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // get one dataset
        $responseGetOne = $this->json('GET', self::TEST_URL_DATASET . '/' . $datasetId, [], $this->header);

        $responseGetOne->assertJsonStructure([
            'message',
            'data'
        ]);
        $responseGetOne->assertStatus(200);

        // delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);

        $responseDeleteDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);

        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }

    /**
     * Create new Dataset with success
     *
     * @return void
     */
    public function test_create_delete_dataset_with_success(): void
    {
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

        $responseCreateTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
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

        // create dataset
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'metadata' => $this->metadata,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // delete dataset
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertJsonStructure([
            'message'
        ]);
        $responseDeleteDataset->assertStatus(200);

        // delete team
        $responseDeleteTeam = $this->json(
            'DELETE',
            self::TEST_URL_TEAM . '/' . $teamId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteTeam->assertJsonStructure([
            'message'
        ]);
        $responseDeleteTeam->assertStatus(200);

        // delete user
        $responseDeleteUser = $this->json(
            'DELETE',
            self::TEST_URL_USER . '/' . $userId,
            [],
            $this->header
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }


    public function test_cannot_delete_dataset_from_another_team(): void
    {
        $dataset = Dataset::where("team_id", "!=", $this->integration->team_id)->first();
        $responseDeleteDataset = $this->json(
            'DELETE',
            self::TEST_URL_DATASET . '/' . $dataset->id . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseDeleteDataset->assertStatus(500)
                              ->assertSeeText("This Application is not allowed to interact with datasets from another team!");

    }

    public function test_cannot_update_dataset_from_another_team(): void
    {
        $dataset = Dataset::where("team_id", "!=", $this->integration->team_id)->first();
        $responseDeleteDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $dataset->id . '?deletePermanently=true',
            $this->metadata,
            $this->header
        );
        $responseDeleteDataset->assertStatus(500)
                              ->assertSeeText("This Application is not allowed to interact with datasets from another team!");

    }

    public function test_cannot_get_without_correct_auth_and_permissions(): void
    {
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'metadata' => $this->metadata,
            ],
            $this->header,
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        //mess up the client-id
        $tempHeader = $this->header;
        $tempHeader['x-client-id'] = 'abcde';

        $responseGetDataset = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $tempHeader
        );
        $responseGetDataset->assertStatus(401)
                            ->assertSeeText("The credentials provided are invalid");

        //mess up the application-id
        $tempHeader = $this->header;
        $tempHeader['x-application-id'] = 'abcde';

        $responseGetDataset = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $tempHeader
        );
        $responseGetDataset->assertStatus(401)
                            ->assertSeeText("No known integration matches the credentials provided");


        //give a bad dataset identifier
        $responseGetDataset = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/' . 1000000 . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseGetDataset->assertStatus(400)
                           ->assertJson([
                                "status" => "INVALID_ARGUMENT",
                                "message" => "Invalid argument(s)",
                                "errors" => [
                                    [
                                        "reason" => "EXISTS",
                                        "message" => "The selected id is invalid.",
                                        "field" => "id"
                                    ]
                                ]
                            ]);

        Application::findOrFail($this->integration->id)->update(["enabled" => false]);

        $responseGetDataset = $this->json(
            'GET',
            self::TEST_URL_DATASET . '/' . $datasetId . '?deletePermanently=true',
            [],
            $this->header
        );
        $responseGetDataset->assertStatus(400)
            ->assertSeeText("Application has not been enabled!");
    }

}
