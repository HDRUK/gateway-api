<?php

namespace App\Services;

use App\Enums\SessionRevokeReason;
use App\Models\UserSession;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

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

    /**
     * Ends every Gateway session a user holds
     *
     * @return array{sessions: int, oauth_tokens: int}
     */
    public static function revokeAllForUser(int $userId, SessionRevokeReason $reason): array
    {
        return DB::transaction(function () use ($userId, $reason) {
            $sessions = UserSession::where('user_id', $userId)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'revoked_reason' => $reason]);

            $accessTokenIds = Passport::token()->where('user_id', $userId)->where('revoked', false)->pluck('id');
            Passport::token()->whereIn('id', $accessTokenIds)->update(['revoked' => true]);
            Passport::refreshToken()->whereIn('access_token_id', $accessTokenIds)->update(['revoked' => true]);

            return ['sessions' => $sessions, 'oauth_tokens' => $accessTokenIds->count()];
        });
    }

    /**
     * Ends every active Gateway session and every OAuth token, for all users
     *
     * @return array{sessions: int, oauth_tokens: int}
     */
    public static function revokeAllActive(SessionRevokeReason $reason): array
    {
        return DB::transaction(function () use ($reason) {
            $sessions = UserSession::whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->update(['revoked_at' => now(), 'revoked_reason' => $reason]);

            $oauthTokens = Passport::token()->where('revoked', false)->update(['revoked' => true]);
            Passport::refreshToken()->where('revoked', false)->update(['revoked' => true]);

            return ['sessions' => $sessions, 'oauth_tokens' => $oauthTokens];
        });
    }
}
