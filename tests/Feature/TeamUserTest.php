<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Role;
use App\Models\TeamHasUser;
use App\Models\TeamUserHasRole;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamUserTest extends TestCase
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

        $this->seed();
        $this->authorisationUser();
        $jwt = $this->getAuthorisationJwt();
        $this->header = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ];
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
        $arrayPermissions = ["developer", "metadata.manager"];
        $arrayPermissionsExpected = ["developer", "metadata.manager"];
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
     * Create Team-User-Roles without success
     * 
     * @return void
     */
    // public function test_create_team_user_permission_and_generate_exception(): void
    // {
    //     $teamId = $this->createTeam();
    //     $url = 'api/v1/teams/' . $teamId . '/users';
    //     $response = $this->json('POST', $url, [], []);
    //     $response->assertStatus(401);

    //     $this->deleteTeam($teamId);
    // }

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
        $arrayPermissionsPost = [
            "developer",
            "hdruk.dar",
        ];
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
        $arrayPermissionsExpected = ["developer", "hdruk.dar", "hdruk.custodian"];
        $payloadPut = [
            "roles" => [
                "hdruk.custodian" => true,
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
     * Create Team-User-Roles remove permission with success
     * 
     * @return void
     */
    public function test_update_team_user_permission_remove_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissionsPost = [
            "developer",
            "hdruk.dar",
        ];
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
        $arrayPermissionsExpected = ["developer", "hdruk.custodian"];
        $payloadPut = [
            "roles" => [
                "hdruk.dar" => false,
                "hdruk.custodian" => true,
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
     * Delete Team-User-Roles with success
     *
     * @return void
     */
    public function test_delete_team_user_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = [
            "developer",
            "hdruk.dar",
        ];
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

    private function createTeam()
    {
        $responseNotification = $this->json(
            'POST',
            'api/v1/notifications',
            [
                'notification_type' => 'applicationSubmitted',
                'message' => 'Some message here',
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
                'member_of' => 1001,
                'contact_point' => 'dinos345@mail.com',
                'application_form_updated_by' => 'Someone Somewhere',
                'application_form_updated_on' => now(),
                'notifications' => [$notificationID],
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

    private function createUser()
    {
        $responseNewUser = $this->json(
            'POST',
            '/api/v1/users',
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
                'orcid' => 75697342,
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
