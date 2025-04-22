<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Tests\Traits\MockExternalApis;

class PermissionTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL = '/api/v1/permissions';

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
            PermissionSeeder::class
        ]);
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
                    'name',
                ]
            ]
        ]);
        $response->assertStatus(200);
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
                    'name',
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
                'name' => 'fake_for_test',
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
                'name' => $name,
            ],
            $this->header
        );

        $checkIfExist = Permission::where(['name' => $name])->count();

        $this->assertTrue((bool) $checkIfExist, 'Response was successfully');

        $response->assertStatus(200);
    }

    public function test_edit_permission_with_success()
    {
        // create
        $responseCreate = $this->json(
            'POST',
            self::TEST_URL,
            [
                'name' => 'fake_for_test',
            ],
            $this->header
        );

        $responseCreate->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentCreate = $responseCreate->decodeResponseJson();
        $this->assertEquals($contentCreate['message'], Config::get('statuscodes.STATUS_CREATED.message'));

        $id = $contentCreate['data'];

        // update
        $responseUpdate = $this->json(
            'PUT',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'fake_for_test_update',
            ],
            $this->header
        );
        $responseUpdate->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentUpdate = $responseUpdate->decodeResponseJson();
        $this->assertEquals($contentUpdate['data']['name'], 'fake_for_test_update');

        // edit
        $responseEdit = $this->json(
            'PATCH',
            self::TEST_URL . '/' . $id,
            [
                'name' => 'fake_for_test_edit',
            ],
            $this->header
        );

        $responseEdit->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
        ->assertJsonStructure([
            'message',
            'data',
        ]);

        $contentEdit = $responseEdit->decodeResponseJson();
        $this->assertEquals($contentEdit['data']['name'], 'fake_for_test_edit');
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
                'name' => 'fake_for_test',
            ],
            $this->header
        );
        $countAfterAdd = Permission::all()->count();
        $this->assertTrue($countBefore + 1 === $countAfterAdd, 'Response was successfully');
        $responseAdd->assertStatus(201);

        $id = $responseAdd['data'];

        $responseDelete = $this->json('DELETE', self::TEST_URL . '/' . $id, [], $this->header);

        $countAfterDelete = Permission::all()->count();

        $this->assertTrue($countBefore === $countAfterDelete, 'Response was successfully');

        $responseDelete->assertStatus(200);
    }
}
