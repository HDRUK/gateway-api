<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Carbon\CarbonImmutable;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint;
use App\Http\Traits\UserRolePermissions;
use App\Exceptions\UnauthorizedException;
use Config;

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
            "alg" => "HS256",
            "typ" => "JWT"
        ];
        $this->payload = [];
        $this->jwt = '';
        $this->secretKey = (string) env('JWT_SECRET');

        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->secretKey)
        );

        $this->config->setValidationConstraints(
            new Constraint\SignedWith($this->config->signer(), $this->config->verificationKey()),
            new Constraint\IssuedBy((string)env('APP_URL')),
            new Constraint\PermittedFor((string)env('APP_URL')),
            new Constraint\StrictValidAt(SystemClock::fromUTC()) // Validates 'exp', 'nbf', 'iat' claims
        );
    }

    /**
     * set jwt value
     *
     * @param string $value
     * @return string
     */
    public function setJwt(string $value): string
    {
        return $this->jwt = $value;
    }

    public function generateToken($userId)
    {
        try {
            $currentTime = CarbonImmutable::now();
            $expireTime = $currentTime->addSeconds(env('JWT_EXPIRATION'));

            $user = User::with([
                'workgroups',
                'teamUsers' => function ($q) {
                    $q->whereHas('roles', function ($r) {
                        $r->where('name', 'custodian.team.admin');
                    })
                        ->with(['team:id,name'])
                        ->select(['id', 'team_id', 'user_id']);
                },
            ])
                ->where([
                    'id' => $userId,
                ])
                ->first();

            // name this as adminTeams (admin_teams)
            // - because we're plucking teams where the user is custodain.team.admin
            $user->setRelation('adminTeams', $user->teamUsers->pluck('team'));
            $user->unsetRelation('teamUsers');

            $token = $this->config->builder()
                ->issuedBy((string)env('APP_URL')) // iss claim
                ->permittedFor((string)env('APP_URL')) // aud claim
                ->relatedTo((string)env('APP_NAME')) // aud claim
                ->identifiedBy(md5(microtime())) // jti claim
                ->issuedAt($currentTime) // iat claim
                ->canOnlyBeUsedAfter($currentTime) // nbf claim
                ->expiresAt($expireTime) // exp claim
                ->withClaim('user', $user) // custom claim - user
                ->withClaim('cohort_discovery_url', Config::get('services.cohort_discovery.init_url'))
                ->getToken($this->config->signer(), $this->config->signingKey());

            $jwt = $token->toString();

            return $jwt;
        } catch (\Exception $e) {
            throw new Exception('Failed to generate token for user :: ' . $e->getMessage());
        }
    }

    public function isValid()
    {
        try {
            // Parse the token
            $token = $this->config->parser()->parse($this->jwt);
            if (!$token instanceof UnencryptedToken) {
                throw new Exception('Token is encrypted and cannot be handled.');
            }

            $constraints = $this->config->validationConstraints();

            $this->config->validator()->assert($token, ...$constraints);

            return true;
        } catch (Exception $e) {
            throw new UnauthorizedException('Invalid token :: ' . $e->getMessage());
        }
    }

    public function decode()
    {
        try {
            // Parse the token
            $token = $this->config->parser()->parse($this->jwt);
            if (!$token instanceof UnencryptedToken) {
                throw new Exception('Token is encrypted and cannot be handled.');
            }

            $claims = $token->claims()->all();

            return $claims;
        } catch (Exception $e) {
            throw new UnauthorizedException('Unable to decode the token :: ' . $e->getMessage());
        }
    }
}
