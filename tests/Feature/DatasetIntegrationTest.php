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
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SpatialCoverageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatasetIntegrationTest extends TestCase
{
    use RefreshDatabase;
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
            EmailTemplateSeeder::class,
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
        $this->addHeaderAppDetails();
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
        $this->addHeaderAppDetails();
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
            $this->adminHeader,
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
            $this->adminHeader,
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
            $this->adminHeader,
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
            $this->adminHeader,
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
            $this->adminHeader,
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
        $this->addHeaderAppDetails();
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
            $this->adminHeader,
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
            $this->adminHeader,
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
            $this->adminHeader,
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
            $this->adminHeader,
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
            $this->adminHeader,
        );
        $responseDeleteUser->assertJsonStructure([
            'message'
        ]);
        $responseDeleteUser->assertStatus(200);
    }


    public function test_cannot_delete_dataset_from_another_team(): void
    {
        $this->addHeaderAppDetails();
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
        $this->addHeaderAppDetails();
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

    public function test_update_dataset_from_other_team_without_success(): void
    {
        $userOneEmail = fake()->email();
        $userTwoEmail = fake()->email();
        $password = 'Passw@rd1!';

        // create user
        $userOne = $this->createUser($userOneEmail, $password);
        // create team one
        $teamOne = $this->createTeam($userOne);
        // assign user one to team one
        $this->assignUserToTeamWithRoles($teamOne, $userOne, ['developer', 'custodian.metadata.manager']);
        // get jwt for user one
        $jwtOne = $this->getJwtByUser($userOneEmail, $password);
        // create application one for team one
        $appOne = $this->newApplication($teamOne, $userOne, $jwtOne, ['datasets.create', 'datasets.read', 'datasets.update', 'datasets.delete']);
        // create dateset with application one
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'metadata' => $this->metadata,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtOne,
                'x-application-id' => $appOne['app_id'],
                'x-client-id' => $appOne['client_id'],
            ],
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        // create user two
        $userTwo = $this->createUser($userTwoEmail, $password);
        // create team two
        $teamTwo = $this->createTeam($userTwo);
        // assign user two to team two
        $this->assignUserToTeamWithRoles($teamTwo, $userTwo, ['developer', 'custodian.metadata.manager']);
        // get jwt for user two
        $jwtTwo = $this->getJwtByUser($userTwoEmail, $password);
        // create application two for team two
        $appTwo = $this->newApplication($teamTwo, $userTwo, $jwtTwo, ['datasets.create', 'datasets.read', 'datasets.update', 'datasets.delete']);
        // update dataset with application two
        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'metadata' => $this->metadata,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtTwo,
                'x-application-id' => $appTwo['app_id'],
                'x-client-id' => $appTwo['client_id'],
            ],
        );
        $responseUpdateDataset->assertStatus(500);
    }

    public function test_create_dataset_by_team_without_success(): void
    {
        $userOneEmail = fake()->email();
        $userTwoEmail = fake()->email();
        $password = 'Passw@rd1!';

        // create user
        $userOne = $this->createUser($userOneEmail, $password);
        // create team one
        $teamOne = $this->createTeam($userOne);
        // assign user one to team one
        $this->assignUserToTeamWithRoles($teamOne, $userOne, ['developer', 'custodian.metadata.manager']);
        // get jwt for user one
        $jwtOne = $this->getJwtByUser($userOneEmail, $password);
        // create application one for team one
        $appOne = $this->newApplication($teamOne, $userOne, $jwtOne, ['datasets.read']);
        // create dateset with application one
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'metadata' => $this->metadata,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtOne,
                'x-application-id' => $appOne['app_id'],
                'x-client-id' => $appOne['client_id'],
            ],
        );
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();

        $this->assertEquals(
            $contentCreateDataset['message'],
            'Application permissions do not allow this request'
        );
    }

    public function test_update_dataset_by_team_without_success(): void
    {
        $userOneEmail = fake()->email();
        $userTwoEmail = fake()->email();
        $password = 'Passw@rd1!';

        // create user
        $userOne = $this->createUser($userOneEmail, $password);
        // create team one
        $teamOne = $this->createTeam($userOne);
        // assign user one to team one
        $this->assignUserToTeamWithRoles($teamOne, $userOne, ['developer', 'custodian.metadata.manager']);
        // get jwt for user one
        $jwtOne = $this->getJwtByUser($userOneEmail, $password);
        // create application one for team one
        $appOne = $this->newApplication($teamOne, $userOne, $jwtOne, ['datasets.create', 'datasets.read', 'datasets.delete']);
        // create dateset with application one
        $responseCreateDataset = $this->json(
            'POST',
            self::TEST_URL_DATASET,
            [
                'metadata' => $this->metadata,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtOne,
                'x-application-id' => $appOne['app_id'],
                'x-client-id' => $appOne['client_id'],
            ],
        );
        $responseCreateDataset->assertStatus(201);
        $contentCreateDataset = $responseCreateDataset->decodeResponseJson();
        $datasetId = $contentCreateDataset['data'];

        $responseUpdateDataset = $this->json(
            'PUT',
            self::TEST_URL_DATASET . '/' . $datasetId,
            [
                'metadata' => $this->metadata,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwtOne,
                'x-application-id' => $appOne['app_id'],
                'x-client-id' => $appOne['client_id'],
            ],
        );
        $this->assertEquals(
            $responseUpdateDataset['message'],
            'Application permissions do not allow this request'
        );
    }

    private function createTeam($userId)
    {
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
                'email' => null,
                'user_id' => $userId,
                'opt_in' => 1,
                'enabled' => 1,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->superUserJwt,
            ],
        );
        $contentNotification = $responseNotification->decodeResponseJson();
        $notificationID = $contentNotification['data'];

        $responseNewTeam = $this->json(
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
                'member_of' => fake()->randomElement([
                    TeamMemberOf::ALLIANCE,
                    TeamMemberOf::HUB,
                    TeamMemberOf::OTHER,
                    TeamMemberOf::NCS,
                ]),
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => now(),
                'notifications' => [$notificationID],
                'users' => [],
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->superUserJwt,
            ],
        );

        $responseNewTeam->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'));

        return $responseNewTeam['data'];
    }

    private function createUser(string $email, string $password)
    {
        $responseNewUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => $email,
                'secondary_email' => fake()->unique()->safeEmail(),
                'preferred_email' => 'primary',
                'password' => $password,
                'sector_id' => 1,
                'organisation' => 'Test Organisation',
                'bio' => 'Test Biography',
                'domain' => 'https://testdomain.com',
                'link' => 'https://testlink.com/link',
                'orcid' => "https://orcid.org/75697342",
                'contact_feedback' => 1,
                'contact_news' => 1,
                'mongo_id' => fake()->numberBetween(0, 100),
                'mongo_object_id' => fake()->regexify('[0-9a-f]{24}'),
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->superUserJwt,
            ],
        );

        $responseNewUser->assertStatus(201);

        return $responseNewUser['data'];
    }

    private function getJwtByUser(string $email, string $password): string
    {
        $authData = [
            'email' => $email,
            'password' => $password,
        ];

        // Authenticate the user and get the JWT token
        $response = $this->json('POST', '/api/v1/auth', $authData, ['Accept' => 'application/json']);

        return $response['access_token'];
    }

    private function assignUserToTeamWithRoles(int $tId, int $uId, array $roles)
    {
        $responseNewRoles = $this->json(
            'POST',
            '/api/v1/teams/' . $tId . '/users',
            [
                "userId" => $uId,
                "roles" => $roles,
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->superUserJwt,
            ],
        );
        $responseNewRoles->assertStatus(201);
    }

    private function newApplication(int $teamId, int $userId, string $jwt, array $permissions = []): array
    {
        $responseCreate = $this->json(
            'POST',
            '/api/v1/applications',
            [
                'name' => 'Hello World',
                'image_link' => 'https://via.placeholder.com/640x480.png/0022dd?text=animals+aliquam',
                'description' => 'Praesentium ut et quae suscipit ut quo adipisci.',
                'team_id' => $teamId,
                'user_id' => $userId,
                'enabled' => true,
                'permissions' => $this->getArrayOfPermissions($permissions),
                "notifications" => [
                    fake()->email(),
                ],
            ],
            [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $jwt,
            ],
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals(
            $contentCreate['message'],
            Config::get('statuscodes.STATUS_CREATED.message')
        );

        return [
            'id' => $contentCreate['data'],
            'app_id' => $responseCreate['data']['app_id'],
            'client_id' => $responseCreate['data']['client_id'],
        ];
    }

    private function addHeaderAppDetails()
    {
        $this->header['x-application-id'] = $this->integration->app_id;
        $this->header['x-client-id'] = $this->integration->client_id;
    }

    private function getArrayOfPermissions(array $array): array
    {
        $return = [];
        $permissions = Permission::whereIn('name', $array)->get();
        foreach ($permissions as $permission) {
            $return[] = $permission->id;
        }
        return $return;
    }
}
