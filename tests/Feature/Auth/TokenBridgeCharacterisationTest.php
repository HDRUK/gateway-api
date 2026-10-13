<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\JwtController;
use App\Http\Middleware\AppendJWTTokenResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RsaSha256;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Characterises AppendJWTTokenResponse, the bridge between the two auth
 * systems.
 *
 * POST /oauth2/token runs Passport's OAuth2 flow and would normally
 * return an RS256 OAuth access token. This middleware intercepts that
 * response, reads the `sub` claim, and swaps the whole thing for a
 * Gateway HS256 token - so a client completing the OAuth2 dance ends up
 * holding an ordinary Gateway session token.
 *
 * That is the single point where the two systems touch, and it is the
 * thing any Passport-isolation work has to either preserve or replace.
 *
 * @see \App\Http\Middleware\AppendJWTTokenResponse
 */
class TokenBridgeCharacterisationTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private User $user;

    public function setUp(): void
    {
        $this->commonSetUp();

        config([
            'passport.public_key' => file_get_contents(storage_path('oauth-public.key')),
            'passport.private_key' => file_get_contents(storage_path('oauth-private.key')),
        ]);

        $this->authorisationUser();
        $this->user = User::where('email', config(self::CFG_TEST_EMAIL))->firstOrFail();
    }

    /**
     * Mint an RS256 token of the shape Passport's token endpoint returns.
     */
    private function passportStyleToken(?string $subject = null, bool $validSignature = true): string
    {
        $config = Configuration::forAsymmetricSigner(
            new RsaSha256(),
            InMemory::plainText(config('passport.' . ($validSignature ? 'private_key' : 'private_key'))),
            InMemory::plainText(config('passport.public_key')),
        );

        $now = new \DateTimeImmutable();

        return $config->builder()
            ->issuedBy(config('app.url'))
            ->permittedFor('cohort-client')
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->relatedTo($subject ?? (string) $this->user->id)
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    private function runBridge(string $accessToken)
    {
        return (new AppendJWTTokenResponse())->handle(
            Request::create('/oauth2/token', 'POST'),
            fn () => response()->json(['access_token' => $accessToken, 'token_type' => 'Bearer']),
        );
    }

    public function test_an_oauth_token_response_is_swapped_for_a_gateway_token(): void
    {
        $response = $this->runBridge($this->passportStyleToken());

        $payload = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('token', $payload);
        $this->assertArrayNotHasKey(
            'access_token',
            $payload,
            'The OAuth token is replaced wholesale, not added alongside.',
        );
    }

    /**
     * The swapped token is an ordinary Gateway token: same signing, same
     * claims, usable as a bearer credential anywhere in the API.
     */
    public function test_the_returned_token_is_a_valid_gateway_token_for_the_subject(): void
    {
        $response = $this->runBridge($this->passportStyleToken());
        $token = json_decode($response->getContent(), true)['token'];

        $jwt = new JwtController();
        $jwt->setJwt($token);

        $this->assertTrue($jwt->isValid());
        $this->assertSame($this->user->id, $jwt->decode()['user']['id']);

        $this->json('GET', '/api/v1/users', [], [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(200);
    }

    /**
     * A response without an access_token passes straight through, which is
     * what keeps OAuth error responses intact.
     */
    public function test_a_response_without_an_access_token_is_untouched(): void
    {
        $response = (new AppendJWTTokenResponse())->handle(
            Request::create('/oauth2/token', 'POST'),
            fn () => response()->json(['error' => 'invalid_grant'], 400),
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['error' => 'invalid_grant'], json_decode($response->getContent(), true));
    }

    /**
     * The incoming token's signature is NOT verified before its `sub` is
     * trusted and a Gateway token minted for that user.
     *
     * This is safe as written, because the middleware reads the response
     * this application generated moments earlier in the same request - it
     * is never client input. It is recorded because the code does not say
     * so, and because any refactor that lets an externally supplied token
     * reach this path turns it into account takeover.
     */
    public function test_the_incoming_token_signature_is_not_verified(): void
    {
        // Flip one leading character of the signature. The segment stays
        // canonical base64url so it still decodes, but it no longer
        // verifies against the signing key.
        $parts = explode('.', $this->passportStyleToken());
        $parts[2][0] = $parts[2][0] === 'A' ? 'B' : 'A';

        $response = $this->runBridge(implode('.', $parts));

        $this->assertArrayHasKey(
            'token',
            json_decode($response->getContent(), true),
            'A token with a broken signature is still converted - trust comes from provenance, not verification.',
        );
    }
}
