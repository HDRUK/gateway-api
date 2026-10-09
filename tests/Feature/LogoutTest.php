<?php

namespace Tests\Feature;

use App\Enums\LoginMethod;
use App\Http\Controllers\JwtController;
use App\Models\User;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    private const LOGOUT_URL = '/api/v1/logout';
    private const SSO_LOGOUT_URL = '/api/oauth/logmeout';
    private const PROTECTED_URL = '/api/v1/features/me';

    protected function setUp(): void
    {
        parent::setUp();

        config(['gateway.gateway_url' => 'https://gateway.test']);
    }

    public function test_logout_ends_the_session_and_clears_the_cookie(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $response = $this->postJson(self::LOGOUT_URL, [], ['Authorization' => 'Bearer ' . $token]);

        $response->assertOk();
        $response->assertCookieExpired('token');
        $this->getJson(self::PROTECTED_URL, ['Authorization' => 'Bearer ' . $token])->assertUnauthorized();
    }

    public function test_logout_leaves_the_users_other_sessions_active(): void
    {
        $user = User::factory()->create();
        $loggingOut = $this->tokenFor($user);
        $otherDevice = $this->tokenFor($user);

        $this->postJson(self::LOGOUT_URL, [], ['Authorization' => 'Bearer ' . $loggingOut])->assertOk();

        $this->getJson(self::PROTECTED_URL, ['Authorization' => 'Bearer ' . $otherDevice])->assertOk();
    }

    public function test_sso_logout_ends_the_session_of_the_token_cookie(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $response = $this->withUnencryptedCookie('token', $token)->get(self::SSO_LOGOUT_URL);

        $response->assertRedirect(config('gateway.gateway_url'));
        $response->assertCookieExpired('token');
        $this->getJson(self::PROTECTED_URL, ['Authorization' => 'Bearer ' . $token])->assertUnauthorized();
    }

    public function test_sso_logout_without_a_token_cookie_still_redirects(): void
    {
        $response = $this->get(self::SSO_LOGOUT_URL);

        $response->assertRedirect(config('gateway.gateway_url'));
        $response->assertCookieExpired('token');
    }

    private function tokenFor(User $user): string
    {
        return (new JwtController())->generateToken($user->id, LoginMethod::PASSWORD);
    }
}
