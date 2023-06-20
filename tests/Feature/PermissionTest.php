<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Permission;
use App\Models\TeamUserHasPermission;
use Tests\Traits\Authorization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;
    use Authorization;

    const TEST_URL = '/api/v1/permissions';

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
     * Get All Permissions with success
     * 
     * @return void
     */
    public function test_get_all_permissions_with_success(): void
    {
        $countPerm = Permission::all()->count();
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $this->assertCount($countPerm, $response['data']);
        $response->assertJsonStructure([
            'message',
            'data' => [
                0 => [
                    'id',
                    'role',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Get All Permmissions with no success
     * 
     * @return void
     */
    public function test_get_all_permissions_and_generate_exception(): void
    {
        $response = $this->json('GET', self::TEST_URL, [], []);
        $response->assertStatus(401);
    }

    /**
     * Get Tag by Id with success
     * 
     * @return void
     */
    public function test_get_permission_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/1', [], $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'message',
            'data' => [
                0 => [
                    'id',
                    'role',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Permission with success
     * 
     * @return void
     */
    public function test_add_new_permission_with_success(): void
    {
        $countBefore = Permission::all()->count();

        $response = $this->json(
            'POST',
            self::TEST_URL . '/',
            [
                'role' => 'fake_for_test',
            ],
            $this->header
        );

        $countAfter = Permission::all()->count();
        $countNewRow = $countAfter - $countBefore;

        $this->assertTrue((bool) $countNewRow, 'Response was successfully');
        $response->assertStatus(201);
    }

    /**
     * Update Permission by Id with success
     *
     * @return void
     */
    public function test_update_permission_by_id_with_success(): void
    {
        $id = 2;
        $name = 'fake_for_test';
        $response = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'role' => $name,
            ],
            $this->header
        );

        $checkIfExist = Permission::where(['role' => $name])->count();

        $this->assertTrue((bool) $checkIfExist, 'Response was successfully');

        $response->assertStatus(200);
    }

    /**
     * Delete Permission by Id with success
     *
     * @return void
     */
    public function test_delete_permission_with_success(): void
    {
        $countBefore = Permission::all()->count();
        $responseAdd = $this->json(
            'POST',
            self::TEST_URL . '/',
            [
                'role' => 'fake_for_test',
            ],
            $this->header
        );
        $countAfterAdd = Permission::all()->count();
        $this->assertTrue($countBefore+1 === $countAfterAdd, 'Response was successfully');
        $responseAdd->assertStatus(201);

        $id = $responseAdd['data'];

        $responseDelete = $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header);

        $countAfterDelete = Permission::all()->count();

        $this->assertTrue($countBefore === $countAfterDelete, 'Response was successfully');

        $responseDelete->assertStatus(200);
    }

    /**
     * Delete Permission by Id and any assign with users and teams with success
     *
     * @return void
     */
    public function test_delete_permission_who_already_exist_with_success(): void
    {
        // choose an id
        $id = 2;

        $countPermissionBefore = Permission::all()->count();
        $countTeamUserHaPermBefore = TeamUserHasPermission::where('permission_id', $id)->count();
        $countAllTeamUserHaPermBefore = TeamUserHasPermission::all()->count();

        $responseDelete = $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header);

        $countPermissionAfter = Permission::all()->count();
        $countTeamUserHaPermAfter = TeamUserHasPermission::where('permission_id', $id)->count();
        $countAllTeamUserHaPermAfter = TeamUserHasPermission::all()->count();

        // perm removed from permissions table
        $this->assertTrue((bool) ($countPermissionBefore - $countPermissionAfter), 'Response was successfully');
        // the permission is not assigned to any user and team
        $this->assertFalse((bool) $countTeamUserHaPermAfter, 'Response was successfully');

        if ($countTeamUserHaPermBefore) {
            $this->assertTrue((bool) ($countAllTeamUserHaPermBefore - $countAllTeamUserHaPermAfter), 'Response was successfully');
        }

        $responseDelete->assertStatus(200);
    }
}
