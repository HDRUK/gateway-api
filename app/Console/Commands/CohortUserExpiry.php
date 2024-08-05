<?php

namespace App\Console\Commands;

use Config;
use Auditor;
use Exception;
use App\Jobs\SendEmailJob;
use App\Models\CohortRequest;
use App\Models\CohortRequestLog;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestHasPermission;
use App\Models\EmailTemplate;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

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
                    $diff = $r->updated_at->diffInDays($now);

                    if ($diff >= Config::get('cohort.cohort_access_expiry_warning_time_in_days')) {
                        if ($r->request_status === 'APPROVED') {
                            $this->sendEmail($r->id, 'WILL_EXPIRE');
                        }
                    }

                    if ($diff >= Config::get('cohort.cohort_access_expiry_time_in_days')) {
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

                            $this->sendEmail($r->id, 'EXPIRED');
                        }
                    }
                }
            }
        }
    }

    private function sendEmail($cohortId, $cohortRequestStatus)
    {
        try {
            $cohort = CohortRequest::where('id', $cohortId)->first();
            $cohortRequestUserId = $cohort['user_id'];
            $user = User::where('id', $cohortRequestUserId)->first();
            $userEmail = ($user['preferred_email'] === 'primary') ?
                $user['email'] : $user['secondary_email'];
            $template = null;
            switch ($cohortRequestStatus) {
                case 'WILL_EXPIRE': // submitted
                    $template = EmailTemplate::where('identifier', '=', 'cohort.discovery.access.will.expire')->first();
                    break;
                case 'EXPIRED':
                    $template = EmailTemplate::where('identifier', '=', 'cohort.discovery.access.expired')->first();
                    break;
            }

            $to = [
                'to' => [
                    'email' => $userEmail,
                    'name' => $user['name'],
                ],
            ];

            $replacements = [
                '[[USER_FIRSTNAME]]' => $user['firstname'],
                '[[EXPIRE_DATE]]' => $cohort['request_expire_at'],
                '[[CURRENT_YEAR]]' => date("Y"),
                '[[USER_EMAIL]]' => $userEmail,
                '[[COHORT_DISCOVERY_ACCESS_URL]]' => Config::get('cohort.cohort_discovery_access_url'),
                '[[COHORT_DISCOVERY_USING_URL]]' => Config::get('cohort.cohort_discovery_using_url'),
                '[[COHORT_DISCOVERY_RENEW_URL]]' => Config::get('cohort.cohort_discovery_renew_url'),
            ];

            SendEmailJob::dispatch($to, $template, $replacements);
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Cohort Request send email :: ' . $e->getMessage());
        }
    }
}
