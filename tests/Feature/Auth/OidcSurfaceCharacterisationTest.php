<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Tests\Traits\GeneratesPassportKeys;
use Tests\Traits\MockExternalApis;

/**
 * Characterisation tests for the OIDC surface the cohort-discovery
 * integration (RQuest) consumes.
 *
 * These endpoints are EXTERNAL CONTRACT. RQuest is configured from the
 * discovery document and validates tokens against the JWKS, so a change
 * here breaks a partner rather than us, and does so silently - nothing in
 * this repository would fail.
 *
 * Nothing in here asserts that the current arrangement is good. It
 * records what is currently served so that any Passport isolation work
 * has to change these deliberately, with the partner informed.
 *
 * @see \App\Http\Controllers\SSO\OpenIdController
 * @see \App\Http\Controllers\SSO\JwksController
 */
class OidcSurfaceCharacterisationTest extends TestCase
{
    use GeneratesPassportKeys;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->usePassportKeys();
    }

    /**
     * Served at two paths because, per the comment in routes/web.php,
     * RQuest calls one or the other unpredictably.
     *
     * @return array<int, array{0: string}>
     */
    public static function discoveryPathProvider(): array
    {
        return [
            'standard well-known path' => ['/.well-known/openid-configuration'],
            'oauth-prefixed path' => ['/oauth/.well-known/openid-configuration'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('discoveryPathProvider')]
    public function test_discovery_document_is_served(string $path): void
    {
        $response = $this->get($path);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'issuer',
            'authorization_endpoint',
            'token_endpoint',
            'jwks_uri',
            'response_types_supported',
            'subject_types_supported',
            'id_token_signing_alg_values_supported',
            'scopes_supported',
            'grant_types_supported',
            'claims_supported',
        ]);
    }

    /**
     * The claims RQuest is told it can rely on. `rquestroles` in
     * particular drives its authorisation, so dropping it would break
     * cohort discovery outright.
     */
    public function test_advertised_claims_include_the_rquest_specific_ones(): void
    {
        $claims = $this->get('/.well-known/openid-configuration')->json('claims_supported');

        foreach (['sub', 'iss', 'aud', 'email', 'rquestroles', 'sid'] as $claim) {
            $this->assertContains($claim, $claims);
        }
    }

    public function test_advertised_scopes_include_the_cohort_discovery_ones(): void
    {
        $scopes = $this->get('/.well-known/openid-configuration')->json('scopes_supported');

        $this->assertEqualsCanonicalizing(
            ['openid', 'email', 'profile', 'rquestroles', 'cohort_discovery_roles'],
            $scopes,
        );
    }

    /**
     * RQuest fetches this to verify token signatures, so the kid here must
     * match the `kid` header JwtController's OIDC tokens are signed with.
     */
    public function test_jwks_exposes_one_rsa_signing_key(): void
    {
        $response = $this->get('/oauth/.well-known/jwks');

        $response->assertStatus(200);
        $response->assertJsonStructure(['keys' => [['kty', 'alg', 'use', 'n', 'e', 'kid']]]);

        $key = $response->json('keys.0');

        $this->assertSame('RSA', $key['kty']);
        $this->assertSame('RS256', $key['alg']);
        $this->assertSame('sig', $key['use']);
        $this->assertSame(config('jwt.kid'), $key['kid']);
    }

    /**
     * The modulus and exponent must be base64url, not plain base64, or
     * consumers cannot parse the key.
     */
    public function test_jwks_key_material_is_base64url_encoded(): void
    {
        $key = $this->get('/oauth/.well-known/jwks')->json('keys.0');

        foreach (['n', 'e'] as $field) {
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9_-]+$/',
                $key[$field],
                "JWKS field [{$field}] must be base64url with no padding.",
            );
        }
    }

    /**
     * DISCREPANCY, recorded deliberately.
     *
     * The discovery document points RQuest at /oauth/authorize, but
     * CohortRequestController builds its redirect to /oauth2/authorize -
     * a different controller, with a different way of identifying the
     * user (encrypted `state` rather than a `cr_uid` session key).
     *
     * Both surfaces are therefore live, and which one RQuest actually
     * uses cannot be determined from this repository. Neither can be
     * retired until that is established from access logs or from the
     * partner. This test exists so the disagreement is visible rather
     * than folklore.
     */
    public function test_discovery_advertises_the_legacy_authorize_endpoint(): void
    {
        $config = $this->get('/.well-known/openid-configuration')->json();

        $this->assertSame(config('app.url') . '/oauth/authorize', $config['authorization_endpoint']);
        $this->assertSame(config('app.url') . '/oauth/token', $config['token_endpoint']);

        $this->assertStringNotContainsString(
            '/oauth2/',
            $config['authorization_endpoint'],
            'Discovery still advertises /oauth/*, while the Gateway redirects users to /oauth2/*.',
        );
    }
}
