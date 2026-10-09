<?php

namespace Tests\Feature;

use App\Enums\LoginMethod;
use App\Enums\SessionRevokeReason;
use App\Http\Controllers\JwtController;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserSessionTest extends TestCase
{
    private const PROTECTED_URL = '/api/v1/features/me';

    public static function tokenTransports(): array
    {
        return [
            'bearer header' => ['bearer'],
            'token cookie' => ['cookie'],
        ];
    }

    #[DataProvider('tokenTransports')]
    public function test_a_token_with_an_active_session_is_accepted(string $transport): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->requestWith($token, $transport)->assertOk();
    }

    #[DataProvider('tokenTransports')]
    public function test_a_token_without_a_session_is_refused(string $transport): void
    {
        $token = $this->tokenFor(User::factory()->create());
        UserSession::query()->delete();

        $this->requestWith($token, $transport)->assertUnauthorized();
    }

    #[DataProvider('tokenTransports')]
    public function test_a_token_whose_session_was_revoked_is_refused(string $transport): void
    {
        $token = $this->tokenFor(User::factory()->create());
        UserSession::query()->update(['revoked_at' => now(), 'revoked_reason' => SessionRevokeReason::ADMIN]);

        $this->requestWith($token, $transport)->assertUnauthorized();
    }

    #[DataProvider('tokenTransports')]
    public function test_a_token_past_its_expiry_is_refused_even_with_an_active_session(string $transport): void
    {
        config(['jwt.expiration' => -60]);
        $token = $this->tokenFor(User::factory()->create());

        $this->requestWith($token, $transport)->assertUnauthorized();
    }

    public function test_each_login_gets_its_own_session(): void
    {
        $user = User::factory()->create();

        $firstId = $this->sessionIdOf($this->tokenFor($user));
        $secondId = $this->sessionIdOf($this->tokenFor($user, LoginMethod::GOOGLE));

        $this->assertNotSame($firstId, $secondId);
        $this->assertSame(
            [[$user->id, LoginMethod::PASSWORD], [$user->id, LoginMethod::GOOGLE]],
            [
                [UserSession::findOrFail($firstId)->user_id, UserSession::findOrFail($firstId)->login_method],
                [UserSession::findOrFail($secondId)->user_id, UserSession::findOrFail($secondId)->login_method],
            ]
        );
    }

    private function tokenFor(User $user, LoginMethod $method = LoginMethod::PASSWORD): string
    {
        return (new JwtController())->generateToken($user->id, $method);
    }

    private function sessionIdOf(string $token): string
    {
        $jwt = new JwtController();
        $jwt->setJwt($token);

        return $jwt->decode()['jti'];
    }

    private function requestWith(string $token, string $transport): TestResponse
    {
        if ($transport === 'cookie') {
            return $this->withCredentials()->withUnencryptedCookie('token', $token)->getJson(self::PROTECTED_URL);
        }

        return $this->getJson(self::PROTECTED_URL, ['Authorization' => 'Bearer ' . $token]);
    }
}
