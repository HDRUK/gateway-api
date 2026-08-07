<?php

namespace App\Console\Commands;

use Config;
use Auditor;
use Exception;
use App\Models\CohortRequest;
use App\Models\CohortRequestLog;
use App\Models\CohortRequestHasLog;
use App\Models\CohortRequestHasPermission;
use App\Services\EmailManager;
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
        $warnings = array_map('intval', explode(',', Config::get('cohort.cohort_access_expiry_warning_times_in_days')));
        $users = User::withTrashed()->with('cohortRequests', 'cohortRequests.permissions')->get();
        foreach ($users as $u) {
            if (count($u->cohortRequests) > 0) {
                foreach ($u->cohortRequests as $r) {
                    $this->handleExpiryCheck($r, $u, 'request_expire_at', $warnings);
                    $this->handleExpiryCheck($r, $u, 'nhse_sde_request_expire_at', $warnings);
                }
            }
        }
    }

    private function handleExpiryCheck(CohortRequest $r, User $u, string $expiryField, array $warnings): void
    {
        $now = Carbon::now();
        $trueExpiryDate = $this->calculateTrueExpiry($r, $expiryField);

        if ($trueExpiryDate === null) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => 'CohortUserExpiry - found null expiry for CohortRequest ID ' . $r->id,
            ]);
            return;
        }

        $diff = (int) round(abs($trueExpiryDate->diffInDays($now)));

        switch ($expiryField) {
            case 'request_expire_at':
                if ($trueExpiryDate <= $now) {
                    if ($r->request_status === CohortRequest::REQUEST_APPROVED) {
                        $this->expireRequest($r, $expiryField);
                        $this->removePermissions($r);
                        $this->createLog($r, $u);
                        $this->sendEmail($r->id, CohortRequest::REQUEST_EXPIRED, $trueExpiryDate);
                    }
                } elseif (in_array($diff, $warnings)) {
                    if ($r->request_status === CohortRequest::REQUEST_APPROVED) {
                        $this->sendEmail($r->id, 'WILL_EXPIRE', $trueExpiryDate);
                    }
                }
                break;

            case 'nhse_sde_request_expire_at':
                if ($trueExpiryDate <= $now) {
                    if ($r->nhse_sde_request_status === CohortRequest::REQUEST_APPROVED) {
                        $this->expireRequest($r, $expiryField);
                        $this->removePermissions($r);
                        $this->createLog($r, $u);
                        $this->sendEmail($r->id, CohortRequest::REQUEST_EXPIRED, $trueExpiryDate);
                    }
                } elseif (in_array($diff, $warnings)) {
                    if ($r->nhse_sde_request_status === CohortRequest::REQUEST_APPROVED) {
                        $this->sendEmail($r->id, 'WILL_EXPIRE', $trueExpiryDate);
                    }
                }
                break;

            default:
                throw new \InvalidArgumentException("Unknown expiry field: {$expiryField}");
        }
    }

    private function createLog(CohortRequest $r, User $user): void
    {
        $log = CohortRequestLog::create([
            'user_id' => $user->id,
            'details' => 'Access expired',
            'request_status' => CohortRequest::REQUEST_EXPIRED,
            'nhse_sde_request_status' => $r->nhse_sde_request_status,
        ]);

        CohortRequestHasLog::create([
            'cohort_request_id' => $r->id,
            'cohort_request_log_id' => $log->id,
        ]);
    }

    private function removePermissions(CohortRequest $r): void
    {
        $permId = Permission::where([
            'application' => 'cohort',
            'name' => 'GENERAL_ACCESS',
        ])->value('id');

        if ($permId) {
            CohortRequestHasPermission::where([
                'cohort_request_id' => $r->id,
                'permission_id' => $permId,
            ])->delete();
        }
    }

    private function expireRequest(CohortRequest $r, string $expiryField): void
    {
        if ($expiryField === 'nhse_sde_request_expire_at') {
            $r->update([
                'nhse_sde_request_status' => CohortRequest::REQUEST_EXPIRED,
                'nhse_sde_request_expire_at' => Carbon::now(),
            ]);
        } else {
            $r->update([
                'request_status' => CohortRequest::REQUEST_EXPIRED,
                'request_expire_at' => Carbon::now(),
            ]);
        }
    }

    private function sendEmail(int $cohortId, string $cohortRequestStatus, Carbon $expiryDate): void
    {
        try {
            $cohort = CohortRequest::where('id', $cohortId)->first();
            $cohortRequestUserId = $cohort['user_id'];
            $user = User::where('id', $cohortRequestUserId)->first();
            $userEmail = ($user['preferred_email'] === 'primary') ?
                $user['email'] : $user['secondary_email'];
            $identifier = null;
            switch ($cohortRequestStatus) {
                case 'WILL_EXPIRE':
                    $identifier = 'cohort.discovery.access.will.expire';
                    break;
                case 'EXPIRED':
                    $identifier = 'cohort.discovery.access.expired';
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
                '[[EXPIRE_DATE]]' => $expiryDate,
                '[[CURRENT_YEAR]]' => date("Y"),
                '[[USER_EMAIL]]' => $userEmail,
                '[[COHORT_DISCOVERY_ACCESS_URL]]' => Config::get('cohort.cohort_discovery_access_url'),
                '[[COHORT_DISCOVERY_USING_URL]]' => Config::get('cohort.cohort_discovery_using_url'),
                '[[COHORT_DISCOVERY_RENEW_URL]]' => Config::get('cohort.cohort_discovery_renew_url'),
            ];

            if ($identifier) {
                app(EmailManager::class)->send($identifier, $to, $replacements);
            }
        } catch (Exception $e) {
            Auditor::log([
                'action_type' => 'EXCEPTION',
                'action_name' => class_basename($this) . '@' . __FUNCTION__,
                'description' => $e->getMessage(),
            ]);

            throw new Exception('Cohort Request send email :: ' . $e->getMessage());
        }
    }

    private function calculateTrueExpiry(CohortRequest $cohort, string $expiryField = 'request_expire_at'): Carbon|null
    {
        /** @var Carbon|null $explicitExpiry */
        $explicitExpiry = $cohort->$expiryField;

        if ($expiryField === 'nhse_sde_request_expire_at') {
            $basedOnUpdatedAt = $cohort->nhse_sde_updated_at
                ? $cohort->nhse_sde_updated_at->copy()->addDays((int) Config::get('cohort.cohort_nhse_sde_access_expiry_time_in_days'))
                : null;
        } else {
            $basedOnUpdatedAt = $cohort->updated_at->copy()->addDays((int) Config::get('cohort.cohort_access_expiry_time_in_days'));
        }

        $candidates = array_filter([$basedOnUpdatedAt, $explicitExpiry]);

        if (empty($candidates)) {
            return null;
        }

        return Carbon::instance(min($candidates));
    }
}
