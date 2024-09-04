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
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        // Configure lcobucci/jwt
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($privateKeyPath),
            InMemory::file($publicKeyPath)
        );

        // Parse the access token
        $token = $config->parser()->parse($accessToken);
        assert($token instanceof Plain);

        // Extract claims
        $claims = $token->claims()->all();
        \Log::info('claims :: ' . json_encode($claims));
        $headers = $token->headers()->all();
        \Log::info('headers :: ' . json_encode($claims));

        // Generate new token with similar claims
        $now = new \DateTimeImmutable();
        $newToken = $config->builder()
                           ->issuedAt($now)
                           ->expiresAt($now->modify('+1 hour'))
                           ->permittedFor('rquest')
                           ->withClaim('typ', 'id')
                           ->getToken($config->signer(), $config->signingKey());

        return $newToken->toString();
    }
}
