<?php

namespace App\Auditor;

use Config;
use Exception;
use App\Models\AuditLog;
use App\Jobs\SendAuditLogToPubSub;
use App\Http\Traits\RequestTransformation;

class Auditor {

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
            $data['action_service'] = 'gateway_api';

            array_walk($data, function(&$value)
            {
                $value = strtolower($value);
            });

            if (Config::get('services.googlepubsub.pubsub_enabled')) {
                SendAuditLogToPubSub::dispatch($data);
            }

            $audit = AuditLog::create($data);

            if (!$audit) {
                return false;
            }
    
            return true;        
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}