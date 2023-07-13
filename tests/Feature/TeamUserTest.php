<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Permission;
use App\Models\TeamHasUser;
use Tests\Traits\Authorization;
use App\Models\TeamUserHasPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Config;

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
     * Create Team-User-Permissions with success
     * 
     * @return void
     */
    public function test_create_team_user_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $url = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["create", "read"];
        $arrayPermissionsExpected = ["create", "read"];
        $payload = [
            "userId" => $userId,
            "permissions" => $arrayPermissions,
        ];
        $this->cleanTeamUserPermissions($teamId, $userId);

        $response = $this->json('POST', $url, $payload, $this->header);
        $response->assertJsonStructure([
            'message'
        ]);
        $response->assertStatus(201);

        $getTeamHasUsers = $this->getTeamHasUsers($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasPermissions = $this->getTeamUserHasPermissions($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamUserHasPermissions) === count($arrayPermissionsExpected)), 'The user in the team has ' . count($arrayPermissionsExpected) . ' permissions');

        $getUserPermissions = $this->getUserPermissions($teamId, $userId);
        $arrayIntersection = array_intersect($arrayPermissions, $getUserPermissions);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));

        $this->deleteTeam($teamId);
    }

    /**
     * Create Team-User-Permissions without success
     * 
     * @return void
     */
    public function test_create_team_user_permission_and_generate_exception(): void
    {
        $teamId = $this->createTeam();
        $url = 'api/v1/teams/' . $teamId . '/users';
        $response = $this->json('POST', $url, [], []);
        $response->assertStatus(401);

        $this->deleteTeam($teamId);
    }

    /**
     * Create Team-User-Permissions add new permission with success
     * 
     * @return void
     */
    public function test_update_team_user_permission_add_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissionsPost = ["create", "read"];
        $payloadPost = [
            "userId" => $userId,
            "permissions" => $arrayPermissionsPost,
        ];
        $this->cleanTeamUserPermissions($teamId, $userId);

        $responsePost = $this->json('POST', $urlPost, $payloadPost, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(201);

        $urlPut = 'api/v1/teams/' . $teamId . '/users/' . $userId;
        $arrayPermissionsExpected = ["create", "read", "update"];
        $arrayPermissionsPut = ["update" => true];
        $payloadPut = [
            "permissions" => $arrayPermissionsPut,
        ];
        $responsePost = $this->json('PUT', $urlPut, $payloadPut, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(200);

        $getTeamHasUsers = $this->getTeamHasUsers($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasPermissions = $this->getTeamUserHasPermissions($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamUserHasPermissions) === count($arrayPermissionsExpected)), 'The user in the team has ' . count($arrayPermissionsExpected) . ' permissions');

        $getUserPermissions = $this->getUserPermissions($teamId, $userId);
        $arrayIntersection = array_intersect($arrayPermissionsExpected, $getUserPermissions);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));

        $this->deleteTeam($teamId);
    }

    /**
     * Create Team-User-Permissions remove permission with success
     * 
     * @return void
     */
    public function test_update_team_user_permission_remove_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissionsPost = ["create", "read"];
        $payloadPost = [
            "userId" => $userId,
            "permissions" => $arrayPermissionsPost,
        ];
        $this->cleanTeamUserPermissions($teamId, $userId);

        $responsePost = $this->json('POST', $urlPost, $payloadPost, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(201);

        $urlPut = 'api/v1/teams/' . $teamId . '/users/' . $userId;
        $arrayPermissionsExpected = ["create"];
        $arrayPermissionsPut = ["read" => false];
        $payloadPut = [
            "permissions" => $arrayPermissionsPut,
        ];
        $responsePost = $this->json('PUT', $urlPut, $payloadPut, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(200);

        $getTeamHasUsers = $this->getTeamHasUsers($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasPermissions = $this->getTeamUserHasPermissions($teamId, $userId);
        $this->assertTrue((bool) (count($getTeamUserHasPermissions) === count($arrayPermissionsExpected)), 'The user in the team has ' . count($arrayPermissionsExpected) . ' permissions');

        $getUserPermissions = $this->getUserPermissions($teamId, $userId);
        $arrayIntersection = array_intersect($arrayPermissionsExpected, $getUserPermissions);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));

        $this->deleteTeam($teamId);
    }

    /**
     * Delete Team-User-Permissions with success
     *
     * @return void
     */
    public function test_delete_team_user_permission_with_success(): void
    {
        $teamId = $this->createTeam();
        $userId = $this->createUser();

        $urlPost = 'api/v1/teams/' . $teamId . '/users';
        $arrayPermissions = ["create", "read"];
        $payload = [
            "userId" => $userId,
            "permissions" => $arrayPermissions,
        ];
        $this->cleanTeamUserPermissions($teamId, $userId);

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
                'name' => 'A. Test Team',
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
            ],
            $this->header,
        );
        $responseNewUser->assertStatus(201);
        
        return $responseNewUser['data'];
    }

    private function cleanTeamUserPermissions($tId, $uId)
    {
        $userhasTeam = TeamHasUser::where('team_id', $tId)->where('user_id', $uId)->first();

        if ($userhasTeam) {
            TeamUserHasPermission::where('team_has_user_id', $userhasTeam->id)->delete();
            TeamHasUser::where('id', $userhasTeam->id)->delete();
        }
    }

    private function getTeamHasUsers($tId, $uId)
    {
        return TeamHasUser::where('team_id', $tId)->where('user_id', $uId)->get()->toArray();
    }

    private function getTeamUserHasPermissions($tId, $uId)
    {
        $userhasTeam = $this->getTeamHasUsers($tId, $uId);
        return $userhasTeam ? TeamUserHasPermission::where('team_has_user_id', $userhasTeam[0]['id'])->get()->toArray() : [];
    }

    private function getUserPermissions($tId, $uId)
    {
        $teamUserHasPermissions = $this->getTeamUserHasPermissions($tId, $uId);

        if (count($teamUserHasPermissions)) {
            $permissions = [];
            foreach ($teamUserHasPermissions as $key => $value) {
                $permissions[] = $value['permission_id'];
            }

            return count($permissions) ? Permission::whereIn('id', $permissions)->pluck('role')->toArray() : [];
        }

        return [];
    }
}
