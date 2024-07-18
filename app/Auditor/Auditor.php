<?php

namespace App\Auditor;

use Config;
use CloudLogger;
use CloudPubSub;
use Exception;
use App\Models\AuditLog;
use App\Http\Traits\RequestTransformation;
use Carbon\CarbonImmutable;

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

            $data['action_service'] = env('AUDIT_ACTION_SERVICE', 'gateway_api');
            $data['action_name'] = strtolower($data['action_name']);
            $data['created_at'] = gettimeofday(true) * 1000000;

            $publish = CloudPubSub::publishMessage($data);
            CloudLogger::write('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));

            CloudPubSub::clearPubSubClient();
            CloudLogger::clearLogging();

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