<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Permission;
use App\Models\CohortRequest;
use Illuminate\Support\Carbon;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Mail;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\MinimalUserSeeder;
use App\Models\CohortRequestHasPermission;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CohortUserExpiryTest extends TestCase
{
    use RefreshDatabase;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimalUserSeeder::class,
            PermissionSeeder::class,
            EmailTemplateSeeder::class,
        ]);
    }

    public function test_it_can_run(): void
    {
        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);
    }

    public function test_it_can_expire_requests(): void
    {
        Mail::fake();

        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVAL REQUESTED',
            'request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(181),
        ]);

        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(181);
        $cr->save();

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
        ]);

        $this->assertDatabaseHas('cohort_request_logs', [
            'user_id' => 1,
            'details' => 'Access expired',
            'request_status' => 'EXPIRED',
            'nhse_sde_request_status' => 'APPROVAL REQUESTED',
        ]);

        $this->assertDatabaseMissing('cohort_request_has_permissions', [
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);
    }

    public function test_it_doesnt_expire_valid_requests(): void
    {
        Mail::fake();

        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVAL REQUESTED',
            'request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(100),
        ]);
        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(100);
        $cr->save();

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
            'request_expire_at' => null,
            'nhse_sde_request_status' => 'APPROVAL REQUESTED',
        ]);

        $this->assertDatabaseHas('cohort_request_has_permissions', [
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);
    }
}
