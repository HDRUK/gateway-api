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
    public function log(User $user, string $description, string $function): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'description' => $description,
            'function' => $function,
        ]);
    }
}