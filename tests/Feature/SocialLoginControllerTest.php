<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Jumbojett\OpenIDConnectClient;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
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
            'services.dta.api_url' => 'https://api.dta.test',
            'services.azure.client_id' => 'fake-azure-client-id',
            'services.azure.client_secret' => 'fake-azure-client-secret',
            'services.azure.redirect' => 'https://api.gateway.test/api/v1/auth/azure/callback',
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

    public function test_openathens_login_round_trip_succeeds_with_the_state_it_issued(): void
    {
        $existingUser = User::factory()->create(['provider' => 'open-athens', 'providerid' => 'the-id']);
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $login = $this->get('/api/v1/auth/openathens?redirect=/search');
        parse_str((string) parse_url($login->headers->get('Location'), PHP_URL_QUERY), $authorizeParams);

        $response = $this->get('/api/v1/auth/openathens/callback?' . http_build_query(['code' => 'some-code', 'state' => $authorizeParams['state']]));

        $response->assertRedirect('https://gateway.test/search');
        $this->assertSame($existingUser->id, $this->loggedInUserId($response));
    }

    public static function callbackPaths(): array
    {
        return [
            'gateway' => ['/api/v1/auth/openathens/callback'],
            'dta' => ['/api/v1/auth/dta/openathens/callback'],
        ];
    }

    #[DataProvider('callbackPaths')]
    public function test_openathens_callback_rejects_a_state_it_did_not_issue(string $path): void
    {
        $existingUser = User::factory()->create(['provider' => 'open-athens', 'providerid' => 'the-id', 'preferred_email' => 'primary']);
        $usersBefore = User::count();
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->openAthensCallback($path, 'a-different-state');

        $this->assertOpenAthensLoginRejected($response, $path);
        $this->assertSame($usersBefore, User::count());
        $this->assertSame('primary', $existingUser->fresh()->preferred_email);
    }

    #[DataProvider('callbackPaths')]
    public function test_openathens_callback_rejects_a_callback_without_a_login_in_this_session(string $path): void
    {
        User::factory()->create(['provider' => 'open-athens', 'providerid' => 'the-id']);
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->withSession(['redirectUrl' => 'https://gateway.test/search'])
            ->get($path . '?code=some-code&state=some-state');

        $this->assertOpenAthensLoginRejected($response, $path);
    }

    public function test_openathens_callback_rejects_a_missing_state(): void
    {
        User::factory()->create(['provider' => 'open-athens', 'providerid' => 'the-id']);
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $response = $this->withSession(['redirectUrl' => 'https://gateway.test/search', 'openathens_state' => 'some-state'])
            ->get('/api/v1/auth/openathens/callback?code=some-code');

        $this->assertOpenAthensLoginRejected($response, '/api/v1/auth/openathens/callback');
    }

    public function test_openathens_callback_does_not_accept_the_same_state_twice(): void
    {
        User::factory()->create(['provider' => 'open-athens', 'providerid' => 'the-id']);
        $this->fakeOpenAthensUserInfo('{"eduPersonTargetedID": "the-id", "eduPersonScopedAffiliation": "member@example.ac.uk"}');

        $this->openAthensCallback()->assertRedirect('https://gateway.test/search');
        $replay = $this->get('/api/v1/auth/openathens/callback?code=some-code&state=some-state');

        $this->assertOpenAthensLoginRejected($replay, '/api/v1/auth/openathens/callback');
    }

    private function assertOpenAthensLoginRejected(TestResponse $response, string $path): void
    {
        if (str_contains($path, '/dta/')) {
            $response->assertStatus(500);
        } else {
            $response->assertRedirect('https://gateway.test/error/500');
        }
        $response->assertCookieMissing('token');
    }

    private function openAthensCallback(string $path = '/api/v1/auth/openathens/callback', string $state = 'some-state'): TestResponse
    {
        return $this->withSession(['redirectUrl' => 'https://gateway.test/search', 'openathens_state' => 'some-state'])
            ->get($path . '?code=some-code&state=' . $state);
    }

    private function fakeOpenAthensUserInfo(string $userInfoJson): void
    {
        $this->app->bind(OpenIDConnectClient::class, fn () => new FakeOpenAthensClient($userInfoJson));
    }

    public static function existingNonAzureProviders(): array
    {
        return [
            'google' => ['google'],
            'registry' => ['registry'],
            'open-athens' => ['open-athens'],
            'service' => ['service'],
            'cruk' => ['cruk'],
        ];
    }

    #[DataProvider('existingNonAzureProviders')]
    public function test_azure_callback_does_not_log_into_an_account_it_only_shares_an_email_with(string $existingProvider): void
    {
        $existingUser = User::factory()->create(['email' => 'existing.user@example.com', 'provider' => $existingProvider, 'providerid' => 'existing-provider-id']);
        Socialite::fake('azure', $this->microsoftUser('attacker-oid', 'existing.user@example.com'));

        $response = $this->azureCallback('/api/v1/auth/azure/callback');

        $response->assertRedirect('https://gateway.test/error/409');
        $response->assertCookieMissing('token');
        $this->assertAccountUnchanged($existingUser);
    }

    public function test_dta_azure_callback_does_not_log_into_an_account_it_only_shares_an_email_with(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing.user@example.com', 'provider' => 'google', 'providerid' => 'existing-provider-id']);
        Socialite::fake('azure', $this->microsoftUser('attacker-oid', 'existing.user@example.com'));

        $response = $this->azureCallback('/api/v1/auth/dta/azure/callback');

        $response->assertRedirect('https://dta.test/error/409');
        $response->assertCookieMissing('token');
        $this->assertAccountUnchanged($existingUser);
    }

    public function test_azure_callback_rejects_a_login_without_a_microsoft_id(): void
    {
        $usersBefore = User::count();
        Socialite::fake('azure', $this->microsoftUser('', 'new.person@example.com'));

        $response = $this->azureCallback('/api/v1/auth/azure/callback');

        $response->assertRedirect('https://gateway.test/error/500');
        $response->assertCookieMissing('token');
        $this->assertSame($usersBefore, User::count());
    }

    public static function azureCallbackPaths(): array
    {
        return [
            'gateway' => ['/api/v1/auth/azure/callback'],
            'dta' => ['/api/v1/auth/dta/azure/callback'],
        ];
    }

    #[DataProvider('azureCallbackPaths')]
    public function test_azure_callback_logs_an_existing_azure_user_into_their_own_account_after_their_email_changes(string $path): void
    {
        $existing = User::factory()->create(['email' => 'old.address@example.com', 'provider' => 'azure', 'providerid' => 'oid-1']);
        Socialite::fake('azure', $this->microsoftUser('oid-1', 'new.address@example.com'));

        $response = $this->azureCallback($path);

        $response->assertRedirect('https://gateway.test/search');
        $this->assertSame($existing->id, $this->loggedInUserId($response));
        $this->assertSame('new.address@example.com', $existing->fresh()->email);
    }

    #[DataProvider('azureCallbackPaths')]
    public function test_azure_callback_creates_an_account_for_a_new_microsoft_user(string $path): void
    {
        Socialite::fake('azure', $this->microsoftUser('oid-new', 'new.person@example.com'));

        $response = $this->azureCallback($path);

        $response->assertRedirect('https://gateway.test/search');
        $newUser = User::where('provider', 'azure')->where('providerid', 'oid-new')->sole();
        $this->assertSame('new.person@example.com', $newUser->email);
        $this->assertSame($newUser->id, $this->loggedInUserId($response));
    }

    #[DataProvider('azureCallbackPaths')]
    public function test_azure_callback_logs_into_the_most_recently_used_account_when_a_microsoft_id_has_several(string $path): void
    {
        User::factory()->create([
            'email' => 'stale.address@example.com', 'provider' => 'azure', 'providerid' => 'oid-shared',
            'updated_at' => now()->subYear(),
        ]);
        $mostRecent = User::factory()->create([
            'email' => 'current.address@example.com', 'provider' => 'azure', 'providerid' => 'oid-shared',
            'updated_at' => now()->subDay(),
        ]);
        Socialite::fake('azure', $this->microsoftUser('oid-shared', 'current.address@example.com'));

        $response = $this->azureCallback($path);

        $response->assertRedirect('https://gateway.test/search');
        $this->assertSame($mostRecent->id, $this->loggedInUserId($response));
    }

    private function microsoftUser(string $id, string $mail): SocialiteUser
    {
        $raw = [
            'id' => $id,
            'displayName' => 'Test User',
            'userPrincipalName' => 'test.user@tenant.example.com',
            'mail' => $mail,
            'givenName' => 'Test',
            'surname' => 'User',
        ];

        return (new SocialiteUser())->setRaw($raw)->map([
            'id' => $raw['id'],
            'nickname' => null,
            'name' => $raw['displayName'],
            'email' => $raw['userPrincipalName'],
            'principalName' => $raw['userPrincipalName'],
            'mail' => $raw['mail'],
            'avatar' => null,
        ]);
    }

    private function azureCallback(string $path): TestResponse
    {
        return $this->withSession(['redirectUrl' => 'https://gateway.test/search'])
            ->get($path . '?code=some-code&state=some-state');
    }

    private function assertAccountUnchanged(User $before): void
    {
        $after = $before->fresh();
        $this->assertSame(
            [$before->provider, $before->providerid, $before->password, $before->email],
            [$after->provider, $after->providerid, $after->password, $after->email]
        );
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
