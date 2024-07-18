<?php

namespace App\Services;

use Config;
use Exception;
use CloudLogger;
use App\Models\AuditLog;
use App\Services\CloudPubSubService;
use App\Http\Traits\RequestTransformation;

class AuditorService {

    use RequestTransformation;

    protected $cloudPubSub;

    public function __construct(CloudPubSubService $cloudPubSub)
    {
        $this->cloudPubSub = $cloudPubSub;
    }

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
                $publish = $this->cloudPubSub->publish($data);
                $this->cloudPubSub->clearPubSubClient();
                gc_collect_cycles();

                if (Config::get('services.googlelogging.enabled')) {
                    CloudLogger::write('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));
                    CloudLogger::clearLogging();
                    gc_collect_cycles();
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