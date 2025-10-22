<?php

namespace App\Auditor;

use Config;
use Exception;
use App\Jobs\AuditLogJob;
use App\Http\Traits\RequestTransformation;

class Auditor
{
    use RequestTransformation;

    /**
     * Logs an action to the audit trail
     *
     * @param array $log
     * @return bool
     */
    public function log(array $log): bool
    {
        try {
            $arrayKeys = [
                'user_id',
                'team_id',
                'target_user_id',
                'target_team_id',
                'action_type',
                'action_name',
                'description',
            ];
            $data = $this->checkEditArray($log, $arrayKeys);
            $data['action_service'] = config('gateway.audit_action_service', 'gateway_api');
            $data['action_name'] = strtolower($data['action_name']);
            $data['created_at'] = gettimeofday(true) * 1000000;

            if (Config::get('services.googlepubsub.enabled')) {
                AuditLogJob::dispatch($data);
            }

            unset($arrayKeys);
            unset($data);

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
