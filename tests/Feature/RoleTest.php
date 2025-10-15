<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Role;
use App\Models\RoleHasPermission;
use Tests\Traits\MockExternalApis;


class RoleTest extends TestCase
{
    
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/roles';

    protected $header = [];

    /**
     * Set up the database
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * Get All Roles with success
     *
     * @return void
     */
    public function test_get_all_roles_with_success(): void
    {
        // get roles
        $response = $this->json('GET', self::TEST_URL, [], $this->header);

        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'full_name',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'permissions',
                ]
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
     * Get Role by Id with success
     *
     * @return void
     */
    public function test_get_role_by_id_with_success(): void
    {
        $response = $this->json('GET', self::TEST_URL . '/2', [], $this->header);

        $this->assertCount(1, $response['data']);
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'full_name',
                    'enabled',
                    'created_at',
                    'updated_at',
                    'permissions',
                ]
            ]
        ]);
        $response->assertStatus(200);
    }

    /**
     * Create new Role with success
     *
     * @return void
     */
    public function test_add_new_role_with_success(): void
    {
        $bodyCreateRole = [
            "name" => "this.is.a.new.role",
            "full_name" => "THIS IS A NEW ROLE",
            "enabled" => true,
            "permissions" => [
                "create",
                "read",
                "test"
            ]
        ];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            $bodyCreateRole,
            $this->header
        );
        $responseCreate->assertStatus(201);
        $contentCreate = $responseCreate->decodeResponseJson();
        $id = $contentCreate['data'];

        $existsRole = Role::where('name', $bodyCreateRole['name'])->get()->toArray();
        $existsPermsForRole = RoleHasPermission::where('role_id', $id)->get()->toArray();

        $this->assertTrue((bool) count($existsRole), 'Response was successfully');
        $this->assertEquals($existsRole[0]['name'], $bodyCreateRole['name']);
        $this->assertEquals($existsRole[0]['full_name'], $bodyCreateRole['full_name']);
        $this->assertEquals(count($existsPermsForRole), count($bodyCreateRole['permissions']));
    }

    /**
     * Create update Role with success
     *
     * @return void
     */
    public function test_update_role_with_success(): void
    {
        // create
        $bodyCreateRole = [
            "name" => "this.is.a.new.role",
            "full_name" => "THIS IS A NEW ROLE",
            "enabled" => true,
            "permissions" => [
                "create",
                "read",
                "test"
            ]
        ];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            $bodyCreateRole,
            $this->header
        );
        $responseCreate->assertStatus(201);
        $contentCreate = $responseCreate->decodeResponseJson();
        $roleId = $contentCreate['data'];

        $existsRole = Role::where('name', $bodyCreateRole['name'])->get()->toArray();
        $existsPermsForRole = RoleHasPermission::where('role_id', $roleId)->get()->toArray();

        $this->assertTrue((bool) count($existsRole), 'Response was successfully');
        $this->assertEquals($existsRole[0]['name'], $bodyCreateRole['name']);
        $this->assertEquals(count($existsPermsForRole), count($bodyCreateRole['permissions']));

        // update
        $bodyUpdateRole = [
            "name" => "this.is.a.new.role.test",
            "full_name" => "UPDATE THIS IS A NEW ROLE",
            "enabled" => true,
            "permissions" => [
                "create",
                "read",
                "update",
                "delete",
            ]
        ];
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $roleId,
            $bodyUpdateRole,
            $this->header
        );

        $responseUpdate->assertStatus(200);

        $existsRoleUpdate = Role::where('name', $bodyUpdateRole['name'])->get()->toArray();
        $existsPermsForRoleUpdate = RoleHasPermission::where('role_id', $roleId)->get()->toArray();

        $this->assertTrue((bool) count($existsRoleUpdate), 'Response was successfully');
        $this->assertEquals($existsRoleUpdate[0]['name'], $bodyUpdateRole['name']);
        $this->assertEquals($existsRoleUpdate[0]['full_name'], $bodyUpdateRole['full_name']);
        $this->assertEquals(count($existsPermsForRoleUpdate), count($bodyUpdateRole['permissions']));
    }

    /**
     * Edit Role with success by id and generate an exception
     *
     * @return void
     */
    public function test_edit_role_with_success(): void
    {
        // create
        $bodyCreateRole = [
            "name" => "this.is.a.new.role",
            "full_name" => "THIS IS A NEW ROLE",
            "enabled" => true,
            "permissions" => [
                "create",
                "read",
                "test"
            ]
        ];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            $bodyCreateRole,
            $this->header
        );
        $responseCreate->assertStatus(201);
        $contentCreate = $responseCreate->decodeResponseJson();
        $roleId = $contentCreate['data'];

        $existsRole = Role::where('name', $bodyCreateRole['name'])->get()->toArray();
        $existsPermsForRole = RoleHasPermission::where('role_id', $roleId)->get()->toArray();

        $this->assertTrue((bool) count($existsRole), 'Response was successfully');
        $this->assertEquals($existsRole[0]['name'], $bodyCreateRole['name']);
        $this->assertEquals($existsRole[0]['full_name'], $bodyCreateRole['full_name']);
        $this->assertEquals(count($existsPermsForRole), count($bodyCreateRole['permissions']));

        // edit
        $bodyEditRole = [
            "name" => "this.is.a.new.role.y",
            "full_name" => "THIS IS A NEW ROLE Y",
        ];
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $roleId,
            $bodyEditRole,
            $this->header
        );

        $responseEdit->assertStatus(200);

        $existsRoleEdit = Role::where('name', $bodyEditRole['name'])->get()->toArray();
        $existsPermsForRoleEdit = RoleHasPermission::where('role_id', $roleId)->get()->toArray();

        $this->assertTrue((bool) count($existsRoleEdit), 'Response was successfully');
        $this->assertEquals($existsRoleEdit[0]['name'], $bodyEditRole['name']);
        $this->assertEquals($existsRoleEdit[0]['full_name'], $bodyEditRole['full_name']);
        $this->assertEquals(count($existsPermsForRoleEdit), count($bodyCreateRole['permissions']));
    }

    /**
     * Delete Role by Id with success
     *
     * @return void
     */
    public function test_delete_role_with_success(): void
    {
        // create
        $bodyCreateRole = [
            "name" => "this.is.a.new.role",
            "full_name" => "THIS IS A NEW ROLE",
            "enabled" => true,
            "permissions" => [
                "create",
                "read",
                "test"
            ]
        ];

        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            $bodyCreateRole,
            $this->header
        );
        $responseCreate->assertStatus(201);
        $contentCreate = $responseCreate->decodeResponseJson();
        $roleId = $contentCreate['data'];

        $existsRole = Role::where('name', $bodyCreateRole['name'])->get()->toArray();
        $existsPermsForRole = RoleHasPermission::where('role_id', $roleId)->get()->toArray();

        $this->assertTrue((bool) count($existsRole), 'Response was successfully');
        $this->assertEquals($existsRole[0]['name'], $bodyCreateRole['name']);
        $this->assertEquals($existsRole[0]['full_name'], $bodyCreateRole['full_name']);
        $this->assertEquals(count($existsPermsForRole), count($bodyCreateRole['permissions']));

        // delete
        $responseDelete = $this->json(
            'DELETE',
            self::TEST_URL . '/' . $roleId,
            [],
            $this->header
        );
        $responseDelete->assertStatus(200);

        $existsRole = Role::where('name', $bodyCreateRole['name'])->get()->toArray();
        $existsPermsForRole = RoleHasPermission::where('role_id', $roleId)->get()->toArray();

        $this->assertFalse((bool) count($existsRole), 'Response was successfully');
        $this->assertFalse((bool) count($existsPermsForRole), 'Response was successfully');
    }
}
