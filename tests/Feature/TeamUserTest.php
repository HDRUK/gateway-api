<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Permission;
use App\Models\TeamHasUser;
use Tests\Traits\Authorization;
use App\Models\TeamUserHasPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamUserTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEAM_ID = 2;
    const USER_ID = 5;

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
        $url = 'api/v1/teams/' . self::TEAM_ID . '/users';
        $arrayPermissions = ["create", "read"];
        $arrayPermissionsExpected = ["create", "read"];
        $payload = [
            "userId" => self::USER_ID,
            "permissions" => $arrayPermissions,
        ];
        $this->cleanTeamUserPermissions(self::TEAM_ID, self::USER_ID);

        $response = $this->json('POST', $url, $payload, $this->header);
        $response->assertJsonStructure([
            'message'
        ]);
        $response->assertStatus(200);

        $getTeamHasUsers = $this->getTeamHasUsers(self::TEAM_ID, self::USER_ID);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasPermissions = $this->getTeamUserHasPermissions(self::TEAM_ID, self::USER_ID);
        $this->assertTrue((bool) (count($getTeamUserHasPermissions) === count($arrayPermissionsExpected)), 'The user in the team has ' . count($arrayPermissionsExpected) . ' permissions');

        $getUserPermissions = $this->getUserPermissions(self::TEAM_ID, self::USER_ID);
        $arrayIntersection = array_intersect($arrayPermissions, $getUserPermissions);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));
    }

    /**
     * Create Team-User-Permissions without success
     * 
     * @return void
     */
    public function test_create_team_user_permission_and_generate_exception(): void
    {
        $url = 'api/v1/teams/' . self::TEAM_ID . '/users';
        $response = $this->json('POST', $url, [], []);
        $response->assertStatus(401);
    }

    /**
     * Create Team-User-Permissions add new permission with success
     * 
     * @return void
     */
    public function test_update_team_user_permission_add_permission_with_success(): void
    {
        $urlPost = 'api/v1/teams/' . self::TEAM_ID . '/users';
        $arrayPermissionsPost = ["create", "read"];
        $payloadPost = [
            "userId" => self::USER_ID,
            "permissions" => $arrayPermissionsPost,
        ];
        $this->cleanTeamUserPermissions(self::TEAM_ID, self::USER_ID);

        $responsePost = $this->json('POST', $urlPost, $payloadPost, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(200);

        $urlPut = 'api/v1/teams/' . self::TEAM_ID . '/users/' . self::USER_ID;
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

        $getTeamHasUsers = $this->getTeamHasUsers(self::TEAM_ID, self::USER_ID);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasPermissions = $this->getTeamUserHasPermissions(self::TEAM_ID, self::USER_ID);
        $this->assertTrue((bool) (count($getTeamUserHasPermissions) === count($arrayPermissionsExpected)), 'The user in the team has ' . count($arrayPermissionsExpected) . ' permissions');

        $getUserPermissions = $this->getUserPermissions(self::TEAM_ID, self::USER_ID);
        $arrayIntersection = array_intersect($arrayPermissionsExpected, $getUserPermissions);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));
    }

    /**
     * Create Team-User-Permissions remove permission with success
     * 
     * @return void
     */
    public function test_update_team_user_permission_remove_permission_with_success(): void
    {
        $urlPost = 'api/v1/teams/' . self::TEAM_ID . '/users';
        $arrayPermissionsPost = ["create", "read"];
        $payloadPost = [
            "userId" => self::USER_ID,
            "permissions" => $arrayPermissionsPost,
        ];
        $this->cleanTeamUserPermissions(self::TEAM_ID, self::USER_ID);

        $responsePost = $this->json('POST', $urlPost, $payloadPost, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(200);

        $urlPut = 'api/v1/teams/' . self::TEAM_ID . '/users/' . self::USER_ID;
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

        $getTeamHasUsers = $this->getTeamHasUsers(self::TEAM_ID, self::USER_ID);
        $this->assertTrue((bool) (count($getTeamHasUsers) === 1), 'Team has one single user');

        $getTeamUserHasPermissions = $this->getTeamUserHasPermissions(self::TEAM_ID, self::USER_ID);
        $this->assertTrue((bool) (count($getTeamUserHasPermissions) === count($arrayPermissionsExpected)), 'The user in the team has ' . count($arrayPermissionsExpected) . ' permissions');

        $getUserPermissions = $this->getUserPermissions(self::TEAM_ID, self::USER_ID);
        $arrayIntersection = array_intersect($arrayPermissionsExpected, $getUserPermissions);
        $this->assertTrue((bool) (count($arrayIntersection) === count($arrayPermissionsExpected)), 'The number of permissions assigned for user in team is ' . count($arrayPermissionsExpected));
    }

    /**
     * Delete Team-User-Permissions with success
     *
     * @return void
     */
    public function test_delete_team_user_permission_with_success(): void
    {
        $urlPost = 'api/v1/teams/' . self::TEAM_ID . '/users';
        $arrayPermissions = ["create", "read"];
        $payload = [
            "userId" => self::USER_ID,
            "permissions" => $arrayPermissions,
        ];
        $this->cleanTeamUserPermissions(self::TEAM_ID, self::USER_ID);

        $responsePost = $this->json('POST', $urlPost, $payload, $this->header);

        $responsePost->assertJsonStructure([
            'message'
        ]);
        $responsePost->assertStatus(200);

        $urlDelete = 'api/v1/teams/' . self::TEAM_ID . '/users/' . self::USER_ID;
        $responseDelete = $this->json('DELETE', $urlDelete, [], $this->header);
        $responseDelete->assertJsonStructure([
            'message'
        ]);
        $responseDelete->assertStatus(200);
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
