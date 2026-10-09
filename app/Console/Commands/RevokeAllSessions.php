<?php

namespace App\Console\Commands;

use Auditor;
use App\Enums\SessionRevokeReason;
use App\Services\UserSessions;
use Illuminate\Console\Command;

class RevokeAllSessions extends Command
{
    protected $signature = 'app:revoke-all-sessions
        {--reason= : Why everyone is being signed out, recorded in the audit log}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Sign out every user and revoke every OAuth token.';

    public function handle(): int
    {
        $reason = trim((string) $this->option('reason'));
        if ($reason === '') {
            $this->error('A --reason is required.');
            return self::FAILURE;
        }

        if (!$this->option('force')
            && !$this->confirm('This signs out every user and revokes every OAuth token. Continue?')) {
            $this->info('Nothing was revoked.');
            return self::FAILURE;
        }

        $revoked = UserSessions::revokeAllActive(SessionRevokeReason::REVOKE_ALL);

        Auditor::log([
            'action_type' => 'UPDATE',
            'action_name' => class_basename($this) . '@' . __FUNCTION__,
            'description' => 'Revoked all ' . $revoked['sessions'] . ' active session(s) and '
                . $revoked['oauth_tokens'] . ' OAuth token(s): ' . $reason,
        ]);

        $this->info('Revoked ' . $revoked['sessions'] . ' session(s) and ' . $revoked['oauth_tokens'] . ' OAuth token(s).');

        return self::SUCCESS;
    }
}
