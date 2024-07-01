<?php

namespace App\Auditor;

use Config;
use Exception;
use Carbon\Carbon;
use App\Models\AuditLog;
use Carbon\CarbonImmutable;
use App\Jobs\SendAuditLogToPubSub;
use App\Http\Traits\RequestTransformation;
use App\Services\LoggingService;

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
            $data['created_at'] = Carbon::now()->timestamp;

            if (Config::get('services.googlepubsub.enabled')) {
                // $logService = new LoggingService();
                // $logService->writeLog(json_encode($data));
                SendAuditLogToPubSub::dispatch($data);
            }

            // unset($data['created_at']);
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