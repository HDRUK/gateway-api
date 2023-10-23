<?php

namespace App\Auditor;

use Carbon\Carbon;
use App\Models\User;
use App\Models\AuditLog;

class Auditor {

    /**
     * Logs an action to the audit trail
     * 
     * @return void
     */
    public function log(int $userId, int $teamId, string $actionType, string $actionService, string $description): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'team_id' => $teamId,
            'action_type' => $actionType,
            'action_service' => $actionService,
            'description' => $description,
        ]);
    }
}