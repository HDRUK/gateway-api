<?php

namespace Tests\Feature;

use App\Enums\LoginMethod;
use App\Enums\SessionRevokeReason;
use App\Models\User;
use App\Models\UserSession;
use Tests\TestCase;

class UserSessionPruningTest extends TestCase
{
    private const PRUNE_URL = '/api/scheduler/prune_user_sessions';

    public function test_sessions_expired_more_than_thirty_days_ago_are_deleted(): void
    {
        $longExpired = $this->sessionExpiring(now()->subDays(31));

        $this->getJson(self::PRUNE_URL)->assertOk();

        $this->assertNull(UserSession::find($longExpired->id));
    }

    public function test_recent_and_active_sessions_are_kept(): void
    {
        $recentlyExpired = $this->sessionExpiring(now()->subDays(29));
        $active = $this->sessionExpiring(now()->addDay());
        $revokedButNotExpired = $this->revokedSessionExpiring(now()->addDay());

        $this->getJson(self::PRUNE_URL)->assertOk();

        $this->assertNotNull(UserSession::find($recentlyExpired->id));
        $this->assertNotNull(UserSession::find($active->id));
        $this->assertNotNull(UserSession::find($revokedButNotExpired->id));
    }

    private function sessionExpiring(\DateTimeInterface $expiresAt): UserSession
    {
        return UserSession::create([
            'user_id' => User::factory()->create()->id,
            'login_method' => LoginMethod::PASSWORD,
            'expires_at' => $expiresAt,
        ]);
    }

    private function revokedSessionExpiring(\DateTimeInterface $expiresAt): UserSession
    {
        $session = $this->sessionExpiring($expiresAt);
        $session->update(['revoked_at' => now(), 'revoked_reason' => SessionRevokeReason::LOGOUT]);

        return $session;
    }
}
