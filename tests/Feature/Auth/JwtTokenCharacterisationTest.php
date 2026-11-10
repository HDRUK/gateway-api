<?php

namespace Tests\Feature\Auth;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\JwtController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Characterisation tests for the tokens minted by JwtController.
 *
 * These confirm the CURRENT format and validation rules before the
 * auth layer is refactored onto a real guard. They are deliberately
 * assertive about things that are arguably wrong (the whole user row is
 * embedded in the payload; `sub` carries the app name rather than the
 * user) — the point is that nothing changes by accident. When a phase of
 * the migration intends to change one of these, the failure here is the
 * prompt to change it consciously and bump the consumers.
 *
 * @see \App\Http\Controllers\JwtController
 */
class JwtTokenCharacterisationTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private JwtController $jwt;

    private User $user;

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->jwt = new JwtController();
        $this->authorisationUser();
        $this->user = User::where('email', config(self::CFG_TEST_EMAIL))->firstOrFail();
    }

    private function claimsFor(?int $userId = null): array
    {
        $token = $this->jwt->generateToken($userId ?? $this->user->id);
        $this->jwt->setJwt($token);

        return $this->jwt->decode();
    }

    /**
     * Anything in the payload is readable by any holder of the token without the signing key.
     */
    public function test_token_is_unencrypted_hs256(): void
    {
        $token = $this->jwt->generateToken($this->user->id);

        $this->assertCount(3, explode('.', $token), 'Expected a three-part JWS.');

        $header = json_decode(
            base64_decode(strtr(explode('.', $token)[0], '-_', '+/')),
            true
        );

        $this->assertSame(['typ' => 'JWT', 'alg' => 'HS256'], $header);
    }

    /**
     * The exact set of top level claims. `cohort_discovery_url` is always
     * present even when the service is unconfigured (it is then null).
     */
    public function test_top_level_claim_set_is_exactly_this(): void
    {
        $claims = $this->claimsFor();

        $this->assertEqualsCanonicalizing(
            ['iss', 'aud', 'sub', 'jti', 'iat', 'nbf', 'exp', 'user', 'cohort_discovery_url'],
            array_keys($claims),
        );
    }

    /**
     * `iss` and `aud` are both app.url, and `sub` is app.name NOT the
     * user id, which is where a standards-compliant token would put it.
     * The user id lives in the custom `user` claim instead.
     */
    public function test_registered_claims_use_app_identity_not_user_identity(): void
    {
        $claims = $this->claimsFor();

        $this->assertSame(config('app.url'), $claims['iss']);
        $this->assertSame([config('app.url')], $claims['aud']);
        $this->assertSame(config('app.name'), $claims['sub']);
        $this->assertSame($this->user->id, $claims['user']['id']);
    }

    /**
     * `jti` is md5(microtime()) unique per mint, but not a stored
     * identifier, so there is nothing to revoke against today.
     */
    public function test_jti_is_unique_per_mint_but_not_persisted(): void
    {
        $first = $this->claimsFor()['jti'];
        $second = $this->claimsFor()['jti'];

        $this->assertNotSame($first, $second);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $first);
    }

    /**
     * Lifetime comes from jwt.expiration, measured from mint time.
     */
    public function test_expiry_is_driven_by_jwt_expiration_config(): void
    {
        $claims = $this->claimsFor();

        $this->assertSame(
            (int) config('jwt.expiration'),
            $claims['exp']->getTimestamp() - $claims['iat']->getTimestamp(),
        );
    }

    /**
     * The ENTIRE users row is serialised into the payload, including
     * columns that are not needed for authorisation and are personal
     * data (secondary_email, hubspot_id, registry_digital_identifier).
     *
     * This is the single biggest reason the token is as large as it is,
     * and it means a permission or profile change is not reflected until
     * the user signs in again. Migrating to a guard should shrink this to
     * an identifier; this test is here so that shrinking it is a
     * deliberate, reviewed act rather than a silent regression for any
     * consumer currently reading these fields out of the token.
     */
    public function test_user_claim_embeds_the_whole_user_row(): void
    {
        $claims = $this->claimsFor();

        // Columns an authorisation decision plausibly needs.
        $this->assertArrayHasKey('id', $claims['user']);
        $this->assertArrayHasKey('is_admin', $claims['user']);

        // Columns it does not which are currently shipped to the client regardless.
        foreach (['secondary_email', 'hubspot_id', 'registry_digital_identifier', 'deleted_at'] as $column) {
            $this->assertArrayHasKey(
                $column,
                $claims['user'],
                "Expected [{$column}] in the user claim; the payload is currently a full row dump.",
            );
        }
    }

    /**
     * Without the CohortDiscoveryService flag, workgroups is an empty
     * array and cohort_admin_teams is absent entirely.
     */
    public function test_workgroups_empty_and_cohort_admin_teams_absent_when_flag_off(): void
    {
        $claims = $this->claimsFor();

        $this->assertSame([], $claims['user']['workgroups']);
        $this->assertArrayNotHasKey('cohort_admin_teams', $claims['user']);
    }

    public function test_a_freshly_minted_token_validates(): void
    {
        $this->jwt->setJwt($this->jwt->generateToken($this->user->id));

        $this->assertTrue($this->jwt->isValid());
    }

    /**
     * Signature, issuer, audience and expiry are all enforced. Each of
     * these must still hold after the guard migration.
     */
    #[DataProvider('rejectedTokenProvider')]
    public function test_invalid_tokens_are_rejected(string $case): void
    {
        $token = $this->forgeToken($case);

        $this->jwt->setJwt($token);

        $this->expectException(UnauthorizedException::class);
        $this->jwt->isValid();
    }

    public static function rejectedTokenProvider(): array
    {
        return [
            'signed with the wrong key' => ['wrong-key'],
            'issued by someone else' => ['wrong-issuer'],
            'intended for another audience' => ['wrong-audience'],
            'already expired' => ['expired'],
            'not a JWT at all' => ['garbage'],
        ];
    }

    private function forgeToken(string $case): string
    {
        if ($case === 'garbage') {
            return 'not-a-token';
        }

        $key = $case === 'wrong-key'
            ? str_repeat('a', 64)
            : (string) config('jwt.secret');

        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($key));

        $now = CarbonImmutable::now();
        $issuer = $case === 'wrong-issuer' ? 'https://evil.example.com' : (string) config('app.url');
        $audience = $case === 'wrong-audience' ? 'https://evil.example.com' : (string) config('app.url');
        $issuedAt = $case === 'expired' ? $now->subDays(30) : $now;
        $expiresAt = $case === 'expired' ? $now->subDays(29) : $now->addHour();

        return $config->builder()
            ->issuedBy($issuer)
            ->permittedFor($audience)
            ->relatedTo((string) config('app.name'))
            ->identifiedBy(md5(microtime()))
            ->issuedAt($issuedAt)
            ->canOnlyBeUsedAfter($issuedAt)
            ->expiresAt($expiresAt)
            ->withClaim('user', ['id' => $this->user->id])
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }
}
