<?php

namespace App\Auditor;

use Config;
use Exception;
use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use App\Services\PubSubService;
use App\Services\LoggingService;
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

            $data['action_service'] = env('AUDIT_ACTION_SERVICE', 'gateway_api');
            $data['action_name'] = strtolower($data['action_name']);

            if (Config::get('services.googlepubsub.enabled')) {
                $data['created_at'] = gettimeofday(true) * 1000000;
                $pubSubService = new PubSubService();
                $publish = $pubSubService->publishMessage($data);

                if (Config::get('services.googlelogging.enabled')) {
                    $loggingService = new LoggingService();
                    $loggingService->writeLog('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));
                }
        
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