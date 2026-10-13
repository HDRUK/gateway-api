<?php

namespace App\Auth;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Records tokens that have been withdrawn before their natural expiry.
 *
 * Our tokens are stateless, so the only way to invalidate one early is to
 * remember that it is no longer welcome. Entries are keyed on the token's
 * `jti` and held only until the token would have expired anyway, which
 * bounds the list: nothing accumulates beyond one token lifetime.
 *
 * IMPORTANT - the backing store must be SHARED across every process that
 * serves requests. A per-container store (file, array, apc) makes this
 * worse than useless: revoking on one replica leaves the token working on
 * every other, while appearing to have succeeded. Point
 * JWT_DENYLIST_CACHE_STORE at redis in any deployment running more than
 * one container. Left unset, the application's default cache store is
 * used, which is correct for single-process and test environments.
 */
class TokenDenylist
{
    private const PREFIX = 'jwt:revoked:';

    public function revoke(string $jti, DateTimeInterface $expiresAt): void
    {
        $secondsRemaining = $expiresAt->getTimestamp() - CarbonImmutable::now()->getTimestamp();

        // Already expired: the signature check will reject it anyway, and
        // storing it would only add an entry nothing can ever match.
        if ($secondsRemaining <= 0) {
            return;
        }

        $this->store()->put(self::PREFIX . $jti, true, $secondsRemaining);
    }

    public function isRevoked(string $jti): bool
    {
        return $this->store()->has(self::PREFIX . $jti);
    }

    private function store(): Repository
    {
        // A null store name resolves the application default.
        return Cache::store(config('jwt.denylist_store'));
    }
}
