<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Jumbojett\OpenIDConnectClient;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fakes\FakeOpenAthensClient;
use Tests\TestCase;
use Tests\Traits\Authorization;

class SocialLoginControllerTest extends TestCase
{
    use Authorization;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gateway.gateway_url' => 'https://gateway.test',
            'services.registry.web_url' => 'https://registry.test',
            'services.registry.login_path' => '/en/keycloak',
            'services.registry.api_url' => 'https://api.registry.test/api/v1',
            'services.registry.handoff_secret' => 'test-handoff-secret',
            'services.openathens.issuer' => 'https://openathens.test',
            'services.openathens.redirect' => 'https://api.gateway.test/api/v1/auth/openathens/callback',
            'services.dta.url' => 'https://dta.test',
        ]);
    }

    public static function unusableOpenAthensIdentifiers(): array
    {
        return [
            'null' => ['null'],
            'list holding null' => ['[null]'],
            'empty string' => ['""'],
            'blank string' => ['"   "'],
            'list holding empty string' => ['[""]'],
            'empty list' => ['[]'],
            'several values' => ['["first-id", "second-id"]'],
            'list holding an object' => ['[{"value": "some-id"}]'],
            'object' => ['{"value": "some-id"}'],
        ];
    }

    #[DataProvider('unusableOpenAthensIdentifiers')]
    public function test_openathens_callback_rejects_an_unusable_targeted_id_instead_of_matching_an_account_without_one(string $identifierJson): void
    {
        $serviceAccount = User::factory()->create(['provider' => 'service', 'providerid' => null, 'preferred_email' => 'primary']);
        $usersBefore = User::count();
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": ' . $identifierJson . ', "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->openAthensCallback();

        $response->assertRedirect('https://gateway.test/error/500');
        $response->assertCookieMissing('token');
        $this->assertSame($usersBefore, User::count());
        $this->assertNotSame('secondary', $serviceAccount->fresh()->preferred_email);
    }

    #[DataProvider('unusableOpenAthensIdentifiers')]
    public function test_openathens_callback_rejects_an_unusable_pairwise_id_when_there_is_no_targeted_id(string $identifierJson): void
    {
        User::factory()->create(['provider' => 'service', 'providerid' => null]);
        $usersBefore = User::count();
        $this->fakeOpenAthensUserInfo('{"pairwiseID": ' . $identifierJson . ', "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->openAthensCallback();

        $response->assertRedirect('https://gateway.test/error/500');
        $response->assertCookieMissing('token');
        $this->assertSame($usersBefore, User::count());
    }

    public function test_openathens_callback_rejects_a_response_without_any_persistent_identifier(): void
    {
        User::factory()->create(['provider' => 'service', 'providerid' => null]);
        $this->fakeOpenAthensUserInfo('{"sub": "non-persistent-sub", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->openAthensCallback();

        $response->assertRedirect('https://gateway.test/error/500');
        $response->assertCookieMissing('token');
    }

    public function test_dta_openathens_callback_rejects_an_unusable_targeted_id(): void
    {
        $serviceAccount = User::factory()->create(['provider' => 'service', 'providerid' => null, 'preferred_email' => 'primary']);
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": null, "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->openAthensCallback('/api/v1/auth/dta/openathens/callback');

        $response->assertStatus(500);
        $response->assertCookieMissing('token');
        $this->assertNotSame('secondary', $serviceAccount->fresh()->preferred_email);
    }

    public static function usableOpenAthensUserInfo(): array
    {
        return [
            'targeted id' => ['{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}'],
            'targeted id in a list' => ['{"eduPersonTargetedID": ["the-id"], "eduPersonScopedAffiliation": ["member@example.ac.uk"]}'],
            'pairwise id only' => ['{"pairwiseID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}'],
            'pairwise id in a list' => ['{"pairwiseID": ["the-id"], "eduPersonScopedAffiliation": "member@example.ac.uk"}'],
            'unusable targeted id with a pairwise id' => ['{"eduPersonTargetedID": null, "pairwiseID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}'],
            'targeted id preferred over pairwise id' => ['{"eduPersonTargetedID": "the-id", "pairwiseID": "another-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}'],
        ];
    }

    #[DataProvider('usableOpenAthensUserInfo')]
    public function test_openathens_callback_creates_a_new_user_from_a_usable_identifier(string $userInfoJson): void
    {
        $this->fakeOpenAthensUserInfo($userInfoJson);

        $response = $this->openAthensCallback();

        $response->assertRedirect('https://gateway.test/account/profile');
        $newUser = User::where('provider', 'open-athens')->where('providerid', 'the-id')->sole();
        $this->assertSame($newUser->id, $this->loggedInUserId($response));
    }

    #[DataProvider('usableOpenAthensUserInfo')]
    public function test_openathens_callback_logs_an_existing_user_into_their_own_account(string $userInfoJson): void
    {
        User::factory()->create(['provider' => 'service', 'providerid' => null]);
        $existingUser = User::factory()->create(['provider' => 'open-athens', 'providerid' => 'the-id']);
        $this->fakeOpenAthensUserInfo($userInfoJson);

        $response = $this->openAthensCallback();

        $response->assertRedirect('https://gateway.test/search');
        $this->assertSame($existingUser->id, $this->loggedInUserId($response));
    }

    public static function otherProviders(): array
    {
        return [
            'google' => ['google'],
            'registry' => ['registry'],
            'service' => ['service'],
        ];
    }

    #[DataProvider('otherProviders')]
    public function test_openathens_callback_does_not_log_into_another_providers_account_with_the_same_id(string $otherProvider): void
    {
        $otherUser = User::factory()->create(['provider' => $otherProvider, 'providerid' => 'the-id', 'preferred_email' => 'primary']);
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->openAthensCallback();

        $response->assertRedirect('https://gateway.test/account/profile');
        $newUser = User::where('provider', 'open-athens')->where('providerid', 'the-id')->sole();
        $this->assertSame($newUser->id, $this->loggedInUserId($response));
        $this->assertSame(
            [$otherProvider, 'the-id', 'primary'],
            [$otherUser->fresh()->provider, $otherUser->fresh()->providerid, $otherUser->fresh()->preferred_email]
        );
    }

    public function test_dta_openathens_callback_does_not_log_into_another_providers_account_with_the_same_id(): void
    {
        User::factory()->create(['provider' => 'google', 'providerid' => 'the-id']);
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->openAthensCallback('/api/v1/auth/dta/openathens/callback');

        $response->assertRedirect('https://dta.test/account/profile');
        $newUser = User::where('provider', 'open-athens')->where('providerid', 'the-id')->sole();
        $this->assertSame($newUser->id, $this->loggedInUserId($response));
    }

    private function openAthensCallback(string $path = '/api/v1/auth/openathens/callback'): TestResponse
    {
        return $this->withSession(['redirectUrl' => 'https://gateway.test/search'])
            ->get($path . '?code=some-code&state=some-state');
    }

    private function fakeOpenAthensUserInfo(string $userInfoJson): void
    {
        $this->app->bind(OpenIDConnectClient::class, fn () => new FakeOpenAthensClient($userInfoJson));
    }

    private function loggedInUserId(TestResponse $response): int
    {
        $token = $response->getCookie('token', false);
        $this->assertNotNull($token, 'expected a token cookie');

        return $this->getUserFromJwt($token->getValue())['id'];
    }

    public function test_registry_callback_redirects_to_conflict_page_on_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'duplicate@example.com',
            'providerid' => 'existing-sub',
            'provider' => 'registry',
        ]);

        Http::fake([
            'https://api.registry.test/api/v1/auth/gateway_handoff/*/redeem' => Http::response([
                'data' => [
                    'sub' => 'a-brand-new-sub',
                    'given_name' => 'Duplicate',
                    'family_name' => 'User',
                    'email' => $existingUser->email,
                ],
            ], 200),
        ]);

        $response = $this->get('/api/v1/auth/registry/callback?code=some-handoff-code');

        $response->assertRedirect('https://gateway.test/error/409');

        $this->assertSame(
            1,
            User::where('email', 'duplicate@example.com')->count(),
            'a colliding insert should not have created a second row'
        );
    }

    public function test_registry_callback_redirects_to_generic_error_page_when_handoff_redemption_fails(): void
    {
        Http::fake([
            'https://api.registry.test/api/v1/auth/gateway_handoff/*/redeem' => Http::response([], 404),
        ]);

        $response = $this->get('/api/v1/auth/registry/callback?code=some-handoff-code');

        $response->assertRedirect('https://gateway.test/error/500');
    }
}
