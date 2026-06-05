<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\SendEmailJob;
use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;
use App\Models\Permission;
use App\Models\CohortRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\MockExternalApis;
use Illuminate\Support\Facades\Mail;
use App\Models\CohortRequestHasPermission;

class CohortUserExpiryTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        Queue::fake([
            LinkageExtraction::class,
            TermExtraction::class,
            SendEmailJob::class,
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

    public function test_it_can_expire_nhse_sde_requests(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(181),
        ]);

        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(100); // standard access not yet expired
        $cr->nhse_sde_updated_at = Carbon::now()->subDays(181); // NHSE SDE access expired
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
            'nhse_sde_request_status' => 'EXPIRED',
            'request_status' => 'APPROVED', // standard access untouched
        ]);

        $this->assertDatabaseHas('cohort_request_logs', [
            'user_id' => 1,
            'details' => 'Access expired',
            'request_status' => 'EXPIRED',
            'nhse_sde_request_status' => 'EXPIRED',
        ]);

        $this->assertDatabaseMissing('cohort_request_has_permissions', [
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);
    }

    public function test_it_doesnt_expire_valid_nhse_sde_requests(): void
    {
        Mail::fake();

        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(181),
        ]);

        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(100);
        $cr->nhse_sde_updated_at = Carbon::now()->subDays(100); // still within the window
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
            'nhse_sde_request_status' => 'APPROVED',
            'request_status' => 'APPROVED',
        ]);

        $this->assertDatabaseHas('cohort_request_has_permissions', [
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);
    }

    public function test_nhse_sde_expiry_does_not_affect_standard_request(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(181),
        ]);

        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(100); // standard NOT expired
        $cr->nhse_sde_updated_at = Carbon::now()->subDays(181); // NHSE SDE expired
        $cr->save();

        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);

        // NHSE SDE should be expired, standard should remain APPROVED
        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'EXPIRED',
        ]);
    }

    public function test_standard_expiry_does_not_affect_nhse_sde_request(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(181),
        ]);

        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(181); // standard expired
        $cr->nhse_sde_updated_at = Carbon::now()->subDays(100); // NHSE SDE NOT expired
        $cr->save();

        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);

        // Standard should be expired, NHSE SDE should remain APPROVED
        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'request_status' => 'EXPIRED',
            'nhse_sde_request_status' => 'APPROVED',
        ]);
    }

    public function test_it_sends_warning_email_when_standard_request_nearing_expiry(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => null,
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(179),
        ]);

        // updated_at + 180 days = now + 1 day; diff = 1; 1 is in the warning window [1,7,14]
        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(179);
        $cr->save();

        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);

        Queue::assertPushed(SendEmailJob::class, 1);

        // Status should NOT have changed to EXPIRED
        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'request_status' => 'APPROVED',
        ]);
    }

    public function test_it_sends_warning_email_when_nhse_sde_request_nearing_expiry(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => null,
            'created_at' => Carbon::now()->subDays(179),
        ]);

        // Both within warning window (1 day left each), but let's make standard not in window
        // and NHSE SDE in window to isolate the test.
        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(100); // standard: 80 days left, not in warning
        $cr->nhse_sde_updated_at = Carbon::now()->subDays(179); // NHSE SDE: 1 day left, in warning
        $cr->save();

        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);

        Queue::assertPushed(SendEmailJob::class, 1);

        // Neither status should have changed to EXPIRED
        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
        ]);
    }

    public function test_it_skips_nhse_sde_check_when_no_dates_are_set(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => null,
            'nhse_sde_updated_at' => null,
            'created_at' => Carbon::now()->subDays(100),
        ]);

        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(100);
        $cr->save();

        $this->artisan('app:cohort-user-expiry')->assertExitCode(0);

        // No NHSE SDE dates — should never touch nhse_sde_request_status
        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'nhse_sde_request_status' => 'APPROVED',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_explicit_nhse_sde_expire_at_date_triggers_expiry(): void
    {
        $req = CohortRequest::create([
            'user_id' => 1,
            'request_status' => 'APPROVED',
            'nhse_sde_request_status' => 'APPROVED',
            'request_expire_at' => null,
            'nhse_sde_request_expire_at' => Carbon::now()->subDay(),
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $cr = CohortRequest::find($req->id);
        $cr->updated_at = Carbon::now()->subDays(10);
        $cr->nhse_sde_updated_at = Carbon::now()->subDays(10); // time-based expiry is 170 days away
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

        // Explicit nhse_sde_request_expire_at was yesterday → must expire even though
        // nhse_sde_updated_at + 180 days is still far in the future.
        $this->assertDatabaseHas('cohort_requests', [
            'id' => $req->id,
            'nhse_sde_request_status' => 'EXPIRED',
            'request_status' => 'APPROVED',
        ]);

        $this->assertDatabaseMissing('cohort_request_has_permissions', [
            'cohort_request_id' => $req->id,
            'permission_id' => $perms->id,
        ]);
    }
}
