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
            'services.google.client_id' => 'fake-google-client-id',
            'services.google.client_secret' => 'fake-google-client-secret',
            'services.google.redirect' => 'https://api.gateway.test/api/v1/auth/google/callback',
            'services.linkedin-openid.client_id' => 'fake-linkedin-client-id',
            'services.linkedin-openid.client_secret' => 'fake-linkedin-client-secret',
            'services.linkedin-openid.redirect' => 'https://api.gateway.test/api/v1/auth/linkedin/callback',
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

    public static function emailLinkedProviders(): array
    {
        return [
            'google' => ['google', 'google'],
            'linkedin' => ['linkedin', 'linkedin-openid'],
        ];
    }

    public static function emailLinkedProvidersAndCallbacks(): array
    {
        $cases = [];
        foreach (self::emailLinkedProviders() as $name => [$segment, $provider]) {
            $cases[$name . ' via gateway'] = [$segment, $provider, '/api/v1/auth/' . $segment . '/callback'];
            $cases[$name . ' via dta'] = [$segment, $provider, '/api/v1/auth/dta/' . $segment . '/callback'];
        }

        return $cases;
    }

    public static function emailLinkedProvidersAndExistingAccounts(): array
    {
        $cases = [];
        foreach (self::emailLinkedProviders() as $name => [$segment, $provider]) {
            $others = ['azure', 'registry', 'open-athens', 'service', 'cruk', $provider === 'google' ? 'linkedin-openid' : 'google'];
            foreach ($others as $existingProvider) {
                $cases[$name . ' with ' . $existingProvider . ' account'] = [$segment, $provider, $existingProvider];
            }
        }

        return $cases;
    }

    #[DataProvider('emailLinkedProvidersAndExistingAccounts')]
    public function test_social_callback_shows_account_exists_when_email_belongs_to_another_account(string $segment, string $provider, string $existingProvider): void
    {
        $existingUser = User::factory()->create(['email' => 'existing.user@example.com', 'provider' => $existingProvider, 'providerid' => 'existing-provider-id']);
        Socialite::fake($provider, $this->socialUser('other-sub', 'existing.user@example.com'));

        $response = $this->socialLoginCallback('/api/v1/auth/' . $segment . '/callback');

        $response->assertRedirect('https://gateway.test/error/409');
        $response->assertCookieMissing('token');
        $this->assertAccountUnchanged($existingUser);
    }

    #[DataProvider('emailLinkedProviders')]
    public function test_dta_social_callback_shows_account_exists_when_email_belongs_to_another_account(string $segment, string $provider): void
    {
        $existingUser = User::factory()->create(['email' => 'existing.user@example.com', 'provider' => 'azure', 'providerid' => 'existing-provider-id']);
        Socialite::fake($provider, $this->socialUser('other-sub', 'existing.user@example.com'));

        $response = $this->socialLoginCallback('/api/v1/auth/dta/' . $segment . '/callback');

        $response->assertRedirect('https://dta.test/error/409');
        $response->assertCookieMissing('token');
        $this->assertAccountUnchanged($existingUser);
    }

    #[DataProvider('emailLinkedProviders')]
    public function test_social_callback_rejects_a_login_without_a_subject_id(string $segment, string $provider): void
    {
        $usersBefore = User::count();
        Socialite::fake($provider, $this->socialUser('', 'new.person@example.com'));

        $response = $this->socialLoginCallback('/api/v1/auth/' . $segment . '/callback');

        $response->assertRedirect('https://gateway.test/error/500');
        $response->assertCookieMissing('token');
        $this->assertSame($usersBefore, User::count());
    }

    #[DataProvider('emailLinkedProvidersAndCallbacks')]
    public function test_social_callback_keeps_an_existing_user_in_their_account_after_their_email_changes(string $segment, string $provider, string $path): void
    {
        $existing = User::factory()->create(['email' => 'old.address@example.com', 'provider' => $provider, 'providerid' => 'sub-1']);
        Socialite::fake($provider, $this->socialUser('sub-1', 'new.address@example.com'));

        $response = $this->socialLoginCallback($path);

        $response->assertRedirect('https://gateway.test/search');
        $this->assertSame($existing->id, $this->loggedInUserId($response));
        $this->assertSame('new.address@example.com', $existing->fresh()->email);
    }

    #[DataProvider('emailLinkedProvidersAndCallbacks')]
    public function test_social_callback_creates_an_account_for_a_new_user(string $segment, string $provider, string $path): void
    {
        Socialite::fake($provider, $this->socialUser('sub-new', 'new.person@example.com'));

        $response = $this->socialLoginCallback($path);

        $response->assertRedirect('https://gateway.test/search');
        $newUser = User::where('provider', $provider)->where('providerid', 'sub-new')->sole();
        $this->assertSame($newUser->id, $this->loggedInUserId($response));
    }

    #[DataProvider('emailLinkedProvidersAndCallbacks')]
    public function test_social_callback_logs_into_the_most_recently_used_account_when_a_subject_id_has_several(string $segment, string $provider, string $path): void
    {
        User::factory()->create([
            'email' => 'stale.address@example.com', 'provider' => $provider, 'providerid' => 'sub-shared',
            'updated_at' => now()->subYear(),
        ]);
        $mostRecent = User::factory()->create([
            'email' => 'current.address@example.com', 'provider' => $provider, 'providerid' => 'sub-shared',
            'updated_at' => now()->subDay(),
        ]);
        Socialite::fake($provider, $this->socialUser('sub-shared', 'current.address@example.com'));

        $response = $this->socialLoginCallback($path);

        $response->assertRedirect('https://gateway.test/search');
        $this->assertSame($mostRecent->id, $this->loggedInUserId($response));
    }

    private function socialUser(string $sub, string $email): SocialiteUser
    {
        $raw = [
            'sub' => $sub,
            'name' => 'Test User',
            'email' => $email,
            'email_verified' => true,
            'given_name' => 'Test',
            'family_name' => 'User',
        ];

        return (new SocialiteUser())->setRaw($raw)->map([
            'id' => $raw['sub'],
            'nickname' => null,
            'name' => $raw['name'],
            'email' => $raw['email'],
            'avatar' => null,
        ]);
    }

    private function socialLoginCallback(string $path): TestResponse
    {
        return $this->withSession(['redirectUrl' => 'https://gateway.test/search'])
            ->get($path . '?code=some-code&state=some-state');
    }

    private function loggedInUserId(TestResponse $response): int
    {
        $token = $response->getCookie('token', false);
        $this->assertNotNull($token, 'expected a token cookie');

        return $this->getUserFromJwt($token->getValue())['id'];
    }

    private const ISSUED_REGISTRY_STATE = '0123456789abcdef0123456789abcdef';
    private const HANDOFF_CODE = 'Ab3dEf6hIj9lMn2pQr5tUv8xYz1bCd4fGh7jKl0n';

    public function test_registry_login_round_trip_succeeds_with_the_state_it_issued(): void
    {
        $this->fakeRegistryRedeem(['sub' => 'registry-sub', 'given_name' => 'Test', 'family_name' => 'User', 'email' => 'new.person@example.com']);

        $login = $this->get('/api/v1/auth/registry');
        parse_str((string) parse_url($login->headers->get('Location'), PHP_URL_QUERY), $registryParams);
        $callbackPath = (string) parse_url($registryParams['external_redirect'], PHP_URL_PATH);

        $response = $this->get($callbackPath . '?code=' . self::HANDOFF_CODE);

        $response->assertRedirect('https://gateway.test');
        $newUser = User::where('provider', 'registry')->where('providerid', 'registry-sub')->sole();
        $this->assertSame($newUser->id, $this->loggedInUserId($response));
    }

    public function test_registry_callback_rejects_a_state_it_did_not_issue(): void
    {
        $this->fakeRegistryRedeem(['sub' => 'registry-sub', 'email' => 'new.person@example.com']);

        $response = $this->withSession(['registry_state' => self::ISSUED_REGISTRY_STATE])
            ->get('/api/v1/auth/registry/callback/fedcba9876543210fedcba9876543210?code=' . self::HANDOFF_CODE);

        $this->assertRegistryLoginRejectedWithoutRedeeming($response);
    }

    public function test_registry_callback_rejects_a_callback_without_a_login_in_this_session(): void
    {
        $this->fakeRegistryRedeem(['sub' => 'registry-sub', 'email' => 'new.person@example.com']);

        $response = $this->get('/api/v1/auth/registry/callback/' . self::ISSUED_REGISTRY_STATE . '?code=' . self::HANDOFF_CODE);

        $this->assertRegistryLoginRejectedWithoutRedeeming($response);
    }

    public function test_registry_callback_does_not_accept_the_same_state_twice(): void
    {
        $this->fakeRegistryRedeem(['sub' => 'registry-sub', 'given_name' => 'Test', 'family_name' => 'User', 'email' => 'new.person@example.com']);

        $this->registryCallbackWithIssuedState(self::HANDOFF_CODE)->assertRedirect('https://gateway.test/search');
        $replay = $this->get('/api/v1/auth/registry/callback/' . self::ISSUED_REGISTRY_STATE . '?code=' . self::HANDOFF_CODE);

        $replay->assertRedirect('https://gateway.test/error/500');
        $replay->assertCookieMissing('token');
    }

    public static function callbacksWithoutAUsableState(): array
    {
        return [
            'no state' => ['/api/v1/auth/registry/callback'],
            'state that is not 32 hex characters' => ['/api/v1/auth/registry/callback/not-a-state'],
        ];
    }

    #[DataProvider('callbacksWithoutAUsableState')]
    public function test_registry_callback_without_a_usable_state_is_not_routed(string $path): void
    {
        $this->fakeRegistryRedeem(['sub' => 'registry-sub', 'email' => 'new.person@example.com']);

        $response = $this->withSession(['registry_state' => self::ISSUED_REGISTRY_STATE])
            ->get($path . '?code=' . self::HANDOFF_CODE);

        $response->assertNotFound();
        $response->assertCookieMissing('token');
        Http::assertNothingSent();
    }

    public static function malformedHandoffCodes(): array
    {
        return [
            'path traversal' => ['../../other-endpoint'],
            'too short' => ['Ab3dEf6h'],
            'too long' => [self::HANDOFF_CODE . 'X'],
            'non alphanumeric' => [substr(self::HANDOFF_CODE, 0, 39) . '%'],
            'missing' => [''],
        ];
    }

    #[DataProvider('malformedHandoffCodes')]
    public function test_registry_callback_rejects_a_malformed_handoff_code_without_calling_registry(string $code): void
    {
        $this->fakeRegistryRedeem(['sub' => 'registry-sub', 'email' => 'new.person@example.com']);

        $response = $this->registryCallbackWithIssuedState(rawurlencode($code));

        $this->assertRegistryLoginRejectedWithoutRedeeming($response);
    }

    public function test_registry_callback_redirects_to_conflict_page_on_duplicate_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'duplicate@example.com',
            'providerid' => 'existing-sub',
            'provider' => 'registry',
        ]);

        $this->fakeRegistryRedeem([
            'sub' => 'a-brand-new-sub',
            'given_name' => 'Duplicate',
            'family_name' => 'User',
            'email' => $existingUser->email,
        ]);

        $response = $this->registryCallbackWithIssuedState(self::HANDOFF_CODE);

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

        $response = $this->registryCallbackWithIssuedState(self::HANDOFF_CODE);

        $response->assertRedirect('https://gateway.test/error/500');
    }

    private function fakeRegistryRedeem(array $data): void
    {
        Http::fake([
            'https://api.registry.test/api/v1/auth/gateway_handoff/*/redeem' => Http::response(['data' => $data], 200),
        ]);
    }

    private function registryCallbackWithIssuedState(string $code): TestResponse
    {
        return $this->withSession(['registry_state' => self::ISSUED_REGISTRY_STATE, 'redirectUrl' => 'https://gateway.test/search'])
            ->get('/api/v1/auth/registry/callback/' . self::ISSUED_REGISTRY_STATE . '?code=' . $code);
    }

    private function assertRegistryLoginRejectedWithoutRedeeming(TestResponse $response): void
    {
        $response->assertRedirect('https://gateway.test/error/500');
        $response->assertCookieMissing('token');
        Http::assertNothingSent();
    }
}
