<?php

namespace App\Auditor;

use App\Facades\CloudLoggerFacade;
use Exception;
use App\Models\AuditLog;
use App\Http\Traits\RequestTransformation;
use App\Services\CloudLoggerService;
use App\Services\CloudPubSubService;

class Auditor {

    use RequestTransformation;

    protected $cloudLogger;
    protected $cloudPubSub;

    /**
     * Constructor
     *
     * @param CloudLoggerService $cloudLogger
     * @param CloudPubSubService $cloudPubSub
     */
    public function __construct()
    {
        // $this->cloudLogger = $cloudLogger;
        // $this->cloudPubSub = $cloudPubSub;
        $this->cloudLogger = new CloudLoggerService();
        $this->cloudPubSub = new CloudPubSubService();
    }

    public function log(array $log)
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

            $publish = $this->cloudPubSub->send($data);
            $this->cloudLogger->write('Message sent to pubsub from "SendAuditLogToPubSub" job ' . json_encode($publish));

            $audit = AuditLog::create($data);
            
            return $audit;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}