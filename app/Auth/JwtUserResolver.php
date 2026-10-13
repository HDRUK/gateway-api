<?php

namespace App\Auth;

use App\Http\Controllers\JwtController;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

/**
 * Resolves the authenticated user for the `jwt` guard.
 *
 * This deliberately mirrors the credential precedence in JwtMiddleware -
 * token cookie first, then a bearer Authorization header - so the guard
 * and the middleware can never disagree about who is signed in.
 *
 * It RESOLVES, it does not ENFORCE. An absent, malformed or expired token
 * yields null, which is what a guard is supposed to report; turning that
 * into a 401 remains JwtMiddleware's job. Throwing here would change the
 * behaviour of every route the guard is merely consulted on.
 */
class JwtUserResolver
{
    public function resolve(Request $request): ?User
    {
        $token = $this->tokenFromRequest($request);

        if ($token === null) {
            return null;
        }

        try {
            $jwt = new JwtController();
            $jwt->setJwt($token);
            $jwt->isValid();

            $claims = $jwt->decode();
        } catch (Throwable) {
            return null;
        }

        $userId = $claims['user']['id'] ?? null;

        if ($userId === null) {
            return null;
        }

        // User::find() respects the SoftDeletes scope, so a token that has
        // outlived its user resolves to null rather than a deleted record.
        return User::find((int) $userId);
    }

    /**
     * Integration requests (x-application-id / x-client-id) authenticate an
     * application rather than a person and carry no token, so they fall
     * through to null here by design.
     */
    public function tokenFromRequest(Request $request): ?string
    {
        if ($request->cookie('token')) {
            return (string) $request->cookie('token');
        }

        $header = (string) $request->header('Authorization');

        if ($header === '') {
            return null;
        }

        $parts = explode(' ', $header);

        if (count($parts) !== 2 || strtolower(trim($parts[0])) !== 'bearer') {
            return null;
        }

        return $parts[1];
    }
}
