<?php

namespace App\Http\Traits;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;

trait CustomIdTokenTrait
{
    public function generateIdToken($accessToken)
    {
        // Load private and public keys
        $privateKey = config('passport.private_key');
        $publicKey = config('passport.public_key');

        // Configure lcobucci/jwt
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($privateKey),
            InMemory::plainText($publicKey)
        );

        // Parse the access token
        $token = $config->parser()->parse($accessToken);
        assert($token instanceof Plain);

        // Extract claims
        $claims = $token->claims()->all();
        $headers = $token->headers()->all();
        \Log::info('headers :: ' . json_encode($headers));

        $profile = [
            $claims['given_name'],
            $claims['family_name'],
        ];

        // Generate new token with similar claims
        $now = new \DateTimeImmutable();
        $newToken = $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+15 days'))
            ->permittedFor($claims['aud'][0])
            ->identifiedBy($claims['jti'])
            ->issuedBy(config('app.url'))
            ->relatedTo($claims['sub'])
            ->withClaim('sid', $claims['session_state'])
            ->withClaim('nonce', $claims['nonce'])
            ->withClaim('typ', 'ID')
            ->withClaim('azh', 'rquest')
            ->withClaim('auth_time', 0)
            ->withClaim('email_verified', true)
            ->withClaim('email', $claims['email'])
            ->withClaim('name', $claims['name'])
            ->withClaim('preferred_username', $claims['preferred_username'])
            ->withClaim('firstname', $claims['given_name'])
            ->withClaim('lastname', $claims['family_name'])
            ->withClaim('profile', $profile)
            ->withClaim('given_name', $claims['given_name'])
            ->withClaim('family_name', $claims['family_name'])
            ->withClaim('rquestroles', $claims['rquestroles'])
            ->withClaim('cohort_discovery_roles', $claims['cohort_discovery_roles'])
            ->withHeader('kid', config('jwt.kid'))
            ->getToken($config->signer(), $config->signingKey());

        return $newToken->toString();
    }
}
