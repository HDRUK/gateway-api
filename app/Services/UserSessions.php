<?php

namespace App\Services;

use App\Enums\SessionRevokeReason;
use App\Models\UserSession;

final class UserSessions
{
    public static function isActive(string $sessionId): bool
    {
        return UserSession::whereKey($sessionId)
            ->whereNull('revoked_at')
            ->exists();
    }

    public static function revokeSession(string $sessionId, SessionRevokeReason $reason): void
    {
        UserSession::whereKey($sessionId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoked_reason' => $reason]);
    }
}
