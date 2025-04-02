<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Role;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use Tests\Traits\Authorization;
use App\Http\Enums\TeamMemberOf;
use Database\Seeders\SectorSeeder;
use Database\Seeders\MinimalUserSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\MockExternalApis;

class TeamUserTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;
    use MockExternalApis{
        setUp as commonSetUp;
    }

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
            SectorSeeder::class,
            EmailTemplateSeeder::class,
        ]);
    }

    /**
     * Create Team-User-Roles with success
     *
     * @return void
     */
    public function test_create_team_user_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $url = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["developer", "custodian.dar.manager"];
        $arrayPermissionsExpected = ["developer", "custodian.dar.manager"];
        $payload = [
            "userId" => $userId,
            "roles" => $arrayPermissions,
        ];
        $this->cleanTeamUserRoles($teamId, $userId);

        $response = $this->json('POST', $url, $payload, $this->header);

        $response->assertJsonStructure([
            'message'
        ]);

        $response->assertStatus(201);

        $getTeamHasUsers = $this->getTeamHasUsers($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasRoles = $this->getTeamUserHasRoles($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamUserHasRoles) === count($arrayPermissionsExpected)), 'The user in the team has ' . count($arrayPermissionsExpected) . ' permissions');

        $getUserRoles = $this->getUserRoles($teamId, $userId);
        $arrayIntersection = array_intersect($arrayPermissions, $getUserRoles);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));

        $this->deleteTeam($teamId);
    }

    /**
     * Create Team-User-Roles add new permission with success
     *
     * @return void
     */
    public function test_update_team_user_permission_add_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();
        $this->cleanTeamUserRoles($teamId, $userId);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissionsPost = ["developer", "custodian.dar.manager"];
        $payloadPost = [
            "userId" => $userId,
            "roles" => $arrayPermissionsPost,
        ];

        $responsePost = $this->json('POST', $urlPost, $payloadPost, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(201);

        $urlPut = 'api/v1/teams/' . $teamId . '/users/' . $userId;
        $arrayPermissionsExpected = ["developer", "custodian.dar.manager", "metadata.editor"];
        $payloadPut = [
            "roles" => [
                "metadata.editor" => true,
            ],
        ];
        $responsePut = $this->json('PUT', $urlPut, $payloadPut, $this->header);

        $responsePut->assertJsonStructure([
            'message'
        ]);
        $responsePut->assertStatus(200);

        $getTeamHasUsers = $this->getTeamHasUsers($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasRoles = $this->getTeamUserHasRoles($teamId, $userId);

        $this->assertTrue((bool) (count($getTeamUserHasRoles) === 3), 'The user in the team has 2 permissions');

        $getUserRoles = $this->getUserRoles($teamId, $userId);
        $arrayIntersection = array_intersect($arrayPermissionsExpected, $getUserRoles);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));

        $this->deleteTeam($teamId);
    }

    /**
     * Create Team-User-Roles add new permission bulk with success
     *
     * @return void
     */
    public function test_update_team_user_roles_remove_roles_bulk_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();
        $this->cleanTeamUserRoles($teamId, $userId);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissionsPost = ["developer", "custodian.dar.manager"];
        $payloadPost = [
            "userId" => $userId,
            "roles" => $arrayPermissionsPost,
        ];

        $responsePost = $this->json('POST', $urlPost, $payloadPost, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(201);

        $urlPut = 'api/v1/teams/' . $teamId . '/users/' . $userId;
        $arrayPermissionsExpected = ["developer", "custodian.dar.manager", "metadata.editor"];
        $payloadPut = [
            "roles" => [
                "metadata.editor" => true,
            ],
        ];
        $responsePut = $this->json('PUT', $urlPut, $payloadPut, $this->header);

        $responsePut->assertJsonStructure([
            'message'
        ]);
        $responsePut->assertStatus(200);

        $getTeamHasUsers = $this->getTeamHasUsers($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasRoles = $this->getTeamUserHasRoles($teamId, $userId);

        $this->assertTrue((bool) (count($getTeamUserHasRoles) === 3), 'The user in the team has 2 permissions');

        $getUserRoles = $this->getUserRoles($teamId, $userId);
        $arrayIntersection = array_intersect($arrayPermissionsExpected, $getUserRoles);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));

        // update bulk
        $urlUpdateBulk = 'api/v1/teams/' . $teamId . '/roles';
        $payloadUpdateBulk = [
            [
                'userId' => $userId,
                'roles' => [
                    'metadata.editor' => false,
                    'dar.reviewer' => true,
                ]
            ],
        ];
        $responseUpdateBulk = $this->json('PATCH', $urlUpdateBulk, $payloadUpdateBulk, $this->header);
        $responseUpdateBulk->assertJsonStructure([
            'message',
            'data'
        ]);
        $responseUpdateBulk->assertStatus(200);

        $this->deleteTeam($teamId);
    }

    /**
     * Create Team-User-Roles remove permission with success
     *
     * @return void
     */
    public function test_update_team_user_permission_remove_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissionsPost = ["developer", "custodian.dar.manager"];
        $payloadPost = [
            "userId" => $userId,
            "roles" => $arrayPermissionsPost,
        ];
        $this->cleanTeamUserRoles($teamId, $userId);

        $responsePost = $this->json('POST', $urlPost, $payloadPost, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(201);

        $urlPut = 'api/v1/teams/' . $teamId . '/users/' . $userId;
        $arrayPermissionsExpected = ["developer", "dar.reviewer"];
        $payloadPut = [
            "roles" => [
                "custodian.dar.manager" => false,
                "dar.reviewer" => true,
            ],
        ];
        $responsePost = $this->json('PUT', $urlPut, $payloadPut, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(200);

        $getTeamHasUsers = $this->getTeamHasUsers($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasRoles = $this->getTeamUserHasRoles($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamUserHasRoles) === 2), 'The user in the team has 2 permissions');

        $getUserRoles = $this->getUserRoles($teamId, $userId);
        $arrayIntersection = array_intersect($arrayPermissionsExpected, $getUserRoles);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));

        $this->deleteTeam($teamId);
    }

    /**
     * Update Team-User-Roles permissions and email the updated permissions
     *
     * @return void
     */
    public function test_update_team_user_permissions_and_send_email(): void
    {

        $initialRoles = ["developer", "dar.reviewer"];
        $teamId = $this->createTeam();
        $userId = $this->createUser();
        $this->createTeamRoles($teamId, $userId, $initialRoles);

        $urlPut = 'api/v1/teams/' . $teamId . '/users/' . $userId;

        //the following should add the "custodian.dar.manager" as a role
        $payloadPut = [
            "roles" => [
                "developer" => true,
                "dar.reviewer" => true,
                "custodian.dar.manager" => true,
            ],
        ];

        $expectedRoles = ["developer", "dar.reviewer", "custodian.dar.manager"];
        $responsePost = $this->json('PUT', $urlPut, $payloadPut, $this->header);
        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(200);
        $userRoles = $this->getUserRoles($teamId, $userId);
        sort($userRoles);
        sort($expectedRoles);
        $this->assertTrue($expectedRoles === $userRoles, 'User now has 3 roles');

        $expectedDispatchedEmails = [
            "developer" => true,
            "dar.reviewer" => true,
            "custodian.dar.manager" => true,
        ];

        $dispatchedEmails = $responsePost['data'];
        $this->assertTrue($dispatchedEmails ===  $expectedDispatchedEmails, 'One email sent for assigning custodian.dar.manager');

        //now the developer role should be removed...
        $payloadPut = [
            "roles" => [
                "developer" => false,
                "dar.reviewer" => true,
                "custodian.dar.manager" => true,
            ],
        ];

        $expectedRoles = ["dar.reviewer", "custodian.dar.manager"];
        $responsePut = $this->json('PUT', $urlPut, $payloadPut, $this->header);
        $responsePut->assertJsonStructure([
            'message'
        ]);
        $responsePut->assertStatus(200);
        $userRoles = $this->getUserRoles($teamId, $userId);
        sort($userRoles);
        sort($expectedRoles);
        $this->assertTrue($expectedRoles === $userRoles, 'Developer role should no longer be present');
        $dispatchedEmails = $responsePut['data'];
        $expectedDispatchedEmails = [
            "developer" => false,
            "dar.reviewer" => true,
            "custodian.dar.manager" => true,
        ];
        $this->assertTrue($dispatchedEmails ===  $expectedDispatchedEmails, 'One email sent for removing developer');

        $this->deleteTeam($teamId);
    }


    /**
     * Delete Team-User-Roles with success
     *
     * @return void
     */
    public function test_delete_team_user_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["developer", "custodian.dar.manager"];
        $payload = [
            "userId" => $userId,
            "roles" => $arrayPermissions,
        ];
        $this->cleanTeamUserRoles($teamId, $userId);

        $responsePost = $this->json('POST', $urlPost, $payload, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(201);

        $urlDelete = 'api/v1/teams/' . $teamId . '/users/' . $userId;
        $responseDelete = $this->json('DELETE', $urlDelete, [], $this->header);
        $responseDelete->assertJsonStructure([
            'message'
        ]);
        $responseDelete->assertStatus(200);

        $this->deleteTeam($teamId);
    }

    public function test_create_user_with_role_metadata_manager_with_success()
    {
        $teamId = $this->createTeam();

        // create user
        $firstUserEmail = fake()->unique()->safeEmail();
        $firstUserId = $this->createUser($firstUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["custodian.metadata.manager"];
        $payload = [
            "userId" => $firstUserId,
            "roles" => $arrayPermissions,
        ];

        $firstResponsePost = $this->json('POST', $urlPost, $payload, $this->header);

        $firstResponsePost->assertJsonStructure([
            'message'
        ]);
        $firstResponsePost->assertStatus(201);

        // create jwt token
        $jwtData = [
            'email' => $firstUserEmail,
            'password' => 'Passw@rd1!',
        ];
        $jwtResponse = $this->json('POST', '/api/v1/auth', $jwtData, ['Accept' => 'application/json']);
        $jwtBearer = $jwtResponse['access_token'];

        // create second user
        $secondUserEmail = fake()->unique()->safeEmail();
        $secondUserId = $this->createUser($secondUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["metadata.editor"];
        $payload = [
            "userId" => $secondUserId,
            "roles" => $arrayPermissions,
        ];

        $secondResponsePost = $this->json('POST', $urlPost, $payload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwtBearer,
        ]);
        $secondResponsePost->assertJsonStructure([
            'message'
        ]);
        $secondResponsePost->assertStatus(201);

        // create third user - without success
        $thirdUserEmail = fake()->unique()->safeEmail();
        $thirdUserId = $this->createUser($thirdUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["developer"];
        $payload = [
            "userId" => $thirdUserId,
            "roles" => $arrayPermissions,
        ];

        $thirdResponsePost = $this->json('POST', $urlPost, $payload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwtBearer,
        ]);
        $thirdResponsePost->assertJsonStructure([
            'code',
            'message',
        ]);
    }

    public function test_update_user_with_role_metadata_manager_with_success()
    {
        $teamId = $this->createTeam();

        // create user
        $firstUserEmail = fake()->unique()->safeEmail();
        $firstUserId = $this->createUser($firstUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["custodian.metadata.manager"];
        $payload = [
            "userId" => $firstUserId,
            "roles" => $arrayPermissions,
        ];

        $firstResponsePost = $this->json('POST', $urlPost, $payload, $this->header);

        $firstResponsePost->assertJsonStructure([
            'message'
        ]);
        $firstResponsePost->assertStatus(201);

        // create jwt token
        $jwtData = [
            'email' => $firstUserEmail,
            'password' => 'Passw@rd1!',
        ];
        $jwtResponse = $this->json('POST', '/api/v1/auth', $jwtData, ['Accept' => 'application/json']);
        $jwtBearer = $jwtResponse['access_token'];

        // create second user
        $secondUserEmail = fake()->unique()->safeEmail();
        $secondUserId = $this->createUser($secondUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["metadata.editor"];
        $payload = [
            "userId" => $secondUserId,
            "roles" => $arrayPermissions,
        ];

        $secondResponsePost = $this->json('POST', $urlPost, $payload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwtBearer,
        ]);
        $secondResponsePost->assertJsonStructure([
            'message'
        ]);
        $secondResponsePost->assertStatus(201);

        // // update second user
        $updateUrlPost = 'api/v1/teams/' . $teamId . '/users/' . $secondUserId;
        $uploadPayload = [
            'roles' => [
                "custodian.metadata.manager" => true,
            ],
        ];

        $updateResponsePost = $this->json('PUT', $updateUrlPost, $uploadPayload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwtBearer,
        ]);

        $updateResponsePost->assertJsonStructure([
            'message'
        ]);
        $updateResponsePost->assertStatus(200);
    }

    public function test_update_user_with_multiple_roles_with_success()
    {
        $teamId = $this->createTeam();

        // first user
        $firstUserEmail = fake()->unique()->safeEmail();
        $firstUserId = $this->createUser($firstUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["custodian.team.admin"];
        $payload = [
            "userId" => $firstUserId,
            "roles" => $arrayPermissions,
        ];

        $firstResponsePost = $this->json('POST', $urlPost, $payload, $this->header);

        $firstResponsePost->assertJsonStructure([
            'message'
        ]);
        $firstResponsePost->assertStatus(201);

        // create jwt token
        $jwtData = [
            'email' => $firstUserEmail,
            'password' => 'Passw@rd1!',
        ];
        $jwtResponse = $this->json('POST', '/api/v1/auth', $jwtData, ['Accept' => 'application/json']);
        $jwtBearer = $jwtResponse['access_token'];

        // second user
        $secondUserEmail = fake()->unique()->safeEmail();
        $secondUserId = $this->createUser($secondUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $secondPermissions = ["metadata.editor"];
        $payload = [
            "userId" => $secondUserId,
            "roles" => $secondPermissions,
        ];

        $secondResponsePost = $this->json('POST', $urlPost, $payload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwtBearer,
        ]);
        $secondResponsePost->assertJsonStructure([
            'message'
        ]);
        $secondResponsePost->assertStatus(201);

        // third user
        $thirdUserEmail = fake()->unique()->safeEmail();
        $thirdUserId = $this->createUser($thirdUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $thirdPermissions = ["metadata.editor"];
        $payload = [
            "userId" => $thirdUserId,
            "roles" => $thirdPermissions,
        ];

        $thirdResponsePost = $this->json('POST', $urlPost, $payload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwtBearer,
        ]);
        $thirdResponsePost->assertJsonStructure([
            'message'
        ]);
        $thirdResponsePost->assertStatus(201);

        // update bulk users
        $multipleUrlPost = 'api/v1/teams/' . $teamId . '/roles';
        $multiplePayload = [
            [
                "userId" => $secondUserId,
                "roles" => [
                    "custodian.metadata.manager" => true,
                    "dar.reviewer" => true,
                ],
            ],
            [
                "userId" => $thirdUserId,
                "roles" => [
                    "metadata.editor" => false,
                    "custodian.metadata.manager" => true,
                    "dar.reviewer" => true,
                ]
            ]
        ];
        $multipleResponsePost = $this->json('PATCH', $multipleUrlPost, $multiplePayload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwtBearer,
        ]);
        $multipleResponsePost->assertJsonStructure([
            'message',
            'data',
        ]);
        $multipleResponsePost->assertStatus(200);
    }

    public function test_delete_last_custodian_team_admin_from_team_without_success()
    {
        $teamId = $this->createTeam();

        // first user
        $firstUserEmail = fake()->unique()->safeEmail();
        $firstUserId = $this->createUser($firstUserEmail);

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["custodian.team.admin"];
        $payload = [
            "userId" => $firstUserId,
            "roles" => $arrayPermissions,
        ];

        $firstResponsePost = $this->json('POST', $urlPost, $payload, $this->header);

        $firstResponsePost->assertJsonStructure([
            'message'
        ]);
        $firstResponsePost->assertStatus(201);

        // delete the user with custodian team admin role
        $urlDelete = 'api/v1/teams/' . $teamId . '/users/' . $firstUserId;
        $responseDelete = $this->json('DELETE', $urlDelete, [], $this->header);
        $responseDelete->assertJsonStructure([
            'code',
            'message',
        ]);
        $responseDelete->assertStatus(500);
    }

    private function createTeam()
    {
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
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
            $this->header,
        );

        $responseNewTeam->assertStatus(Config::get('statuscodes.STATUS_OK.code'));

        return $responseNewTeam['data'];
    }

    private function deleteTeam($id)
    {
        $responseDelete = $this->json(
            'DELETE',
            'api/v1/teams/' . $id . '?deletePermanently=true',
            [],
            $this->header,
        );
        $responseDelete->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
        ]);
    }

    private function createUser(?string $email = null)
    {
        $responseNewUser = $this->json(
            'POST',
            '/api/v1/users',
            [
                'firstname' => 'Firstname',
                'lastname' => 'Lastname',
                'email' => $email ? $email : 'firstname.lastname.123456789@test.com',
                'secondary_email' => fake()->unique()->safeEmail(),
                'preferred_email' => 'primary',
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

        $responseNewUser->assertStatus(201);

        return $responseNewUser['data'];
    }

    private function createTeamRoles(int $tId, int $uId, array $roles)
    {
        $responseNewRoles = $this->json(
            'POST',
            '/api/v1/teams/' . $tId . '/users',
            [
                "userId" => $uId,
                "roles" => $roles,
            ],
            $this->header,
        );
        $responseNewRoles->assertStatus(201);
    }

    private function cleanTeamUserRoles($tId, $uId)
    {
        $userhasTeam = TeamHasUser::where('team_id', $tId)->where('user_id', $uId)->first();

        if ($userhasTeam) {
            TeamUserHasRole::where('team_has_user_id', $userhasTeam->id)->delete();
            TeamHasUser::where('id', $userhasTeam->id)->delete();
        }
    }

    private function getTeamHasUsers($tId, $uId)
    {
        return TeamHasUser::where('team_id', $tId)->where('user_id', $uId)->get()->toArray();
    }

    private function getTeamUserHasRoles($tId, $uId)
    {
        $userhasTeam = $this->getTeamHasUsers($tId, $uId);
        return $userhasTeam ? TeamUserHasRole::where('team_has_user_id', $userhasTeam[0]['id'])->get()->toArray() : [];
    }

    private function getUserRoles($tId, $uId)
    {
        $teamUserHasRoles = $this->getTeamUserHasRoles($tId, $uId);

        if (count($teamUserHasRoles)) {
            $roles = [];
            foreach ($teamUserHasRoles as $key => $value) {
                $roles[] = $value['role_id'];
            }

            return count($roles) ? Role::whereIn('id', $roles)->pluck('name')->toArray() : [];
        }

        return [];
    }
}
