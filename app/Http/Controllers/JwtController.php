<?php

namespace App\Http\Controllers;

use App\Exceptions\UnauthorizedException;
use App\Http\Traits\UserRolePermissions;
use App\Models\User;
use Carbon\CarbonImmutable;
use Config;
use Exception;
use Laravel\Pennant\Feature;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;

class JwtController extends Controller
{
    use UserRolePermissions;

    private string $secretKey;

    private array $header;

    private array $payload;

    private string $jwt;

    private Configuration $config;

    public function __construct()
    {
        $this->header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];
        $this->payload = [];
        $this->jwt = '';
        $this->secretKey = (string) config('jwt.secret');

        $this->config = Configuration::forSymmetricSigner(
            new Sha256,
            InMemory::plainText($this->secretKey)
        );

        $this->config->setValidationConstraints(
            new Constraint\SignedWith($this->config->signer(), $this->config->verificationKey()),
            new Constraint\IssuedBy((string) config('app.url')),
            new Constraint\PermittedFor((string) config('app.url')),
            new Constraint\StrictValidAt(SystemClock::fromUTC()) // Validates 'exp', 'nbf', 'iat' claims
        );
    }

    /**
     * set jwt value
     */
    public function setJwt(string $value): string
    {
        return $this->jwt = $value;
    }

    public function generateToken($userId)
    {
        try {
            $currentTime = CarbonImmutable::now();
            $expireTime = $currentTime->addSeconds(intval(config('jwt.expiration')));

            $user = User::find($userId);
            //$user->workgroups->makeHidden('pivot');

            if (Feature::active('CohortDiscoveryService')) {
                $user->cohortAdminTeams->setVisible(['id', 'name']);

                /*
                - cohort discovery service will self manage workgroups now
                - leave this code in, incase we want to return to gateway managed
                $userClaim = $user->toArray();
                $userClaim['workgroups'] = $user->workgroups
                    ->where('active', 1)
                    ->pluck('name')
                    ->values()
                    ->all();
                */

            }

            $token = $this->config->builder()
                ->issuedBy((string) config('app.url')) // iss claim
                ->permittedFor((string) config('app.url')) // aud claim
                ->relatedTo((string) config('app.name')) // aud claim
                ->identifiedBy(md5(microtime())) // jti claim
                ->issuedAt($currentTime) // iat claim
                ->canOnlyBeUsedAfter($currentTime) // nbf claim
                ->expiresAt($expireTime) // exp claim
                ->withClaim('user', $user) // custom claim - user
                ->withClaim('cohort_discovery_url', Config::get('services.cohort_discovery_service.init_url'))
                ->getToken($this->config->signer(), $this->config->signingKey());

            $jwt = $token->toString();

            return $jwt;
        } catch (\Exception $e) {
            throw new Exception('Failed to generate token for user :: '.$e->getMessage());
        }
    }

    public function isValid()
    {
        try {
            // Parse the token
            $token = $this->config->parser()->parse($this->jwt);
            if (! $token instanceof UnencryptedToken) {
                throw new Exception('Token is encrypted and cannot be handled.');
            }

            $constraints = $this->config->validationConstraints();

            $this->config->validator()->assert($token, ...$constraints);

            return true;
        } catch (Exception $e) {
            throw new UnauthorizedException('Invalid token :: '.$e->getMessage());
        }
    }

    public function decode()
    {
        try {
            // Parse the token
            $token = $this->config->parser()->parse($this->jwt);
            if (! $token instanceof UnencryptedToken) {
                throw new Exception('Token is encrypted and cannot be handled.');
            }

            $claims = $token->claims()->all();

            return $claims;
        } catch (Exception $e) {
            throw new UnauthorizedException('Unable to decode the token :: '.$e->getMessage());
        }
    }
}
