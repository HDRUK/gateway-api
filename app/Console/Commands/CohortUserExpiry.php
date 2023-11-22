<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use App\Models\CohortRequestLog;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestHasPermission;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CohortUserExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cohort-user-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nightly process to check length of cohort user being active and expiring if over threshold.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $users = User::withTrashed()->with('cohortRequests', 'cohortRequests.permissions')->get();
        foreach ($users as $u) {
            if (count($u->cohortRequests) > 0) {
                foreach ($u->cohortRequests as $r) {
                    $now = Carbon::now();
                    $diff = $r->created_at->diffInDays($now);

                    if ($diff >= env('COHORT_ACCESS_EXPIRY_WARNING_TIME_IN_DAYS', 166)) {
                        // TODO - Trigger expiration warning email - AC has strikethrough?!
                    }

                    if ($diff >= env('COHORT_ACCESS_EXPIRY_TIME_IN_DAYS', 180)) {
                        if ($r->request_status === 'APPROVED') {
                            $r->update([
                                'request_status' => 'EXPIRED',
                                'cohort_status' => false,
                                'request_expire_at' => Carbon::now(),
                            ]);
                            foreach ($r->permissions as $p) {
                                // Remove GENERAL_ACCESS permission from request
                                $perm = Permission::where([
                                    'application' => 'cohort',
                                    'name' => 'GENERAL_ACCESS',
                                ])->first();

                                CohortRequestHasPermission::where([
                                    'cohort_request_id' => $r->id,
                                    'permission_id' => $perm->id,
                                ])->delete();
                            }

                            // Log and associate with this request
                            $log = CohortRequestLog::create([
                                'user_id' => $u->id,
                                'details' => 'Access expired',
                                'request_status' => 'EXPIRED',
                            ]);

                            CohortRequestHasLog::create([
                                'cohort_request_id' => $r->id,
                                'cohort_request_log_id' => $log->id,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
