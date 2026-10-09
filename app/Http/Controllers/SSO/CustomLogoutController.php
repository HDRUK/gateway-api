<?php

namespace App\Http\Controllers\SSO;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Enums\SessionRevokeReason;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\JwtController;
use App\Services\UserSessions;

class CustomLogoutController extends Controller
{
    /**
     * constructor
     */
    public function __constructor()
    {
        //
    }

    public function rquestLogout(Request $request)
    {
        try {
            // no info from rquest in logout
            // $user = Auth::user();
            // Auth::guard()->logout();
            $this->revokeTokenCookieSession($request);
            $request->session()->flush();

            $redirectUrl = config('gateway.gateway_url');
            return redirect()->away($redirectUrl)->withCookie(Cookie::forget('token'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function revokeTokenCookieSession(Request $request): void
    {
        $token = $request->cookie('token');
        if (!is_string($token) || $token === '') {
            return;
        }

        $jwt = new JwtController();
        $jwt->setJwt($token);
        try {
            $jwt->isValid();
        } catch (UnauthorizedException) {
            return;
        }

        $sessionId = $jwt->decode()['jti'] ?? null;
        if (is_string($sessionId)) {
            UserSessions::revokeSession($sessionId, SessionRevokeReason::LOGOUT);
        }
    }
}
