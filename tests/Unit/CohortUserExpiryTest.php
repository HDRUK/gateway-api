<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Models\Permission;
use App\Models\CohortRequest;
use App\Models\CohortRequestHasPermission;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\MinimalUserSeeder;

use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CohortUserExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    public function test_it_can_run(): void
    {
        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);
    }

    public function test_it_can_expire_requests(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'cohort_status' => true,
            'request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(181),
        ]);

        $perms = Permission::where([
            'application' => 'cohort',
            'name' => 'GENERAL_ACCESS',
        ])->first();

        CohortRequestHasPermission::create([
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);

        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);

        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'request_status' => 'EXPIRED',
            'cohort_status' => false,
        ]);

        $this->assertDatabaseHas('cohort_request_logs', [
            'user_id' => 1,
            'details' => 'Access expired',
            'request_status' => 'EXPIRED',
        ]);

        $this->assertDatabaseMissing('cohort_request_has_permissions', [
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);
    }

    public function test_it_doesnt_expire_valid_requests(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'cohort_status' => true,
            'request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(100),
        ]);

        $perms = Permission::where([
            'application' => 'cohort',
            'name' => 'GENERAL_ACCESS',
        ])->first();

        CohortRequestHasPermission::create([
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);

        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);

        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'request_status' => 'APPROVED',
            'cohort_status' => true,
            'request_expire_at' => null,
        ]);

        $this->assertDatabaseHas('cohort_request_has_permissions', [
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);
    }
}