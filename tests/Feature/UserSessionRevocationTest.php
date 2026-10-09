<?php

namespace Tests\Feature;

use App\Enums\LoginMethod;
use App\Http\Controllers\JwtController;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class UserSessionRevocationTest extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const PROTECTED_URL = '/api/v1/features/me';

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    public function test_a_superadmin_revoking_a_users_sessions_ends_all_of_them(): void
    {
        $user = User::factory()->create();
        $firstToken = $this->tokenFor($user);
        $secondToken = $this->tokenFor($user, LoginMethod::GOOGLE);
        [$accessTokenId, $refreshTokenId] = $this->passportTokensFor($user);

        $this->postJson($this->revokeUrl($user->id), [], $this->header)->assertOk();

        $this->getJson(self::PROTECTED_URL, $this->bearer($firstToken))->assertUnauthorized();
        $this->getJson(self::PROTECTED_URL, $this->bearer($secondToken))->assertUnauthorized();
        $this->assertTrue(Passport::token()->findOrFail($accessTokenId)->revoked);
        $this->assertTrue((bool) Passport::refreshToken()->findOrFail($refreshTokenId)->revoked);
    }

    public function test_revoking_one_users_sessions_leaves_other_users_signed_in(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherToken = $this->tokenFor($otherUser);
        [$otherAccessTokenId] = $this->passportTokensFor($otherUser);

        $this->postJson($this->revokeUrl($user->id), [], $this->header)->assertOk();

        $this->getJson(self::PROTECTED_URL, $this->bearer($otherToken))->assertOk();
        $this->assertFalse(Passport::token()->findOrFail($otherAccessTokenId)->revoked);
    }

    public function test_a_user_can_sign_in_again_after_their_sessions_are_revoked(): void
    {
        $user = User::factory()->create();
        $this->tokenFor($user);

        $this->postJson($this->revokeUrl($user->id), [], $this->header)->assertOk();

        $this->getJson(self::PROTECTED_URL, $this->bearer($this->tokenFor($user)))->assertOk();
    }

    public function test_a_non_superadmin_cannot_revoke_sessions(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);
        $this->authorisationUser(false);
        $nonAdminHeader = $this->bearer($this->getAuthorisationJwt(false));

        $this->postJson($this->revokeUrl($user->id), [], $nonAdminHeader)->assertUnauthorized();

        $this->getJson(self::PROTECTED_URL, $this->bearer($token))->assertOk();
    }

    public function test_revoking_the_sessions_of_an_unknown_user_is_rejected(): void
    {
        $this->postJson($this->revokeUrl(User::max('id') + 1), [], $this->header)->assertStatus(400);
    }

    public function test_revoking_all_sessions_ends_every_active_session(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstToken = $this->tokenFor($firstUser);
        $secondToken = $this->tokenFor($secondUser, LoginMethod::AZURE);
        [$firstAccessTokenId, $firstRefreshTokenId] = $this->passportTokensFor($firstUser);
        [$secondAccessTokenId] = $this->passportTokensFor($secondUser);

        $this->artisan('app:revoke-all-sessions', ['--reason' => 'Test reason', '--force' => true])
            ->assertSuccessful();

        $this->getJson(self::PROTECTED_URL, $this->bearer($firstToken))->assertUnauthorized();
        $this->getJson(self::PROTECTED_URL, $this->bearer($secondToken))->assertUnauthorized();
        $this->assertTrue(Passport::token()->findOrFail($firstAccessTokenId)->revoked);
        $this->assertTrue(Passport::token()->findOrFail($secondAccessTokenId)->revoked);
        $this->assertTrue((bool) Passport::refreshToken()->findOrFail($firstRefreshTokenId)->revoked);
    }

    public function test_signing_in_after_all_sessions_are_revoked_works(): void
    {
        $user = User::factory()->create();
        $this->tokenFor($user);

        $this->artisan('app:revoke-all-sessions', ['--reason' => 'Test reason', '--force' => true])
            ->assertSuccessful();

        $this->getJson(self::PROTECTED_URL, $this->bearer($this->tokenFor($user)))->assertOk();
    }

    public function test_revoking_all_sessions_changes_nothing_unless_confirmed(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->artisan('app:revoke-all-sessions', ['--reason' => 'Test reason'])
            ->expectsConfirmation('This signs out every user and revokes every OAuth token. Continue?', 'no')
            ->assertFailed();

        $this->getJson(self::PROTECTED_URL, $this->bearer($token))->assertOk();
    }

    public function test_revoking_all_sessions_requires_a_reason(): void
    {
        $token = $this->tokenFor(User::factory()->create());

        $this->artisan('app:revoke-all-sessions', ['--force' => true])->assertFailed();

        $this->getJson(self::PROTECTED_URL, $this->bearer($token))->assertOk();
    }

    private function revokeUrl(int $userId): string
    {
        return '/api/v1/admin/users/' . $userId . '/revoke-sessions';
    }

    private function tokenFor(User $user, LoginMethod $method = LoginMethod::PASSWORD): string
    {
        return (new JwtController())->generateToken($user->id, $method);
    }

    private function bearer(string $token): array
    {
        return ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token];
    }

    /**
     * @return array{0: string, 1: string} access token id, refresh token id
     */
    private function passportTokensFor(User $user): array
    {
        $client = Client::factory()->create();
        $accessToken = Passport::token()->forceCreate([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'client_id' => $client->getKey(),
            'scopes' => [],
            'revoked' => false,
            'expires_at' => now()->addDay(),
        ]);
        $refreshToken = Passport::refreshToken()->forceCreate([
            'id' => Str::random(40),
            'access_token_id' => $accessToken->id,
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        return [$accessToken->id, $refreshToken->id];
    }
}
