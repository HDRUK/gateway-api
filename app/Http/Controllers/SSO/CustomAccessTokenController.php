<?php

namespace App\Http\Controllers\SSO;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use Illuminate\Http\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as Psr7Response;
use League\OAuth2\Server\Exception\OAuthServerException;

class CustomAccessTokenController extends PassportAccessTokenController
{
    public function issueOAuthToken(Request $request)
    {
        \Log::debug('Oauth request body', $request->all());
        \Log::debug('Oauth headers', $request->headers->all());

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $psrRequest = $psrHttpFactory->createRequest($request);

        $psrRequest = $psrRequest->withParsedBody($request->all() ?? []);

        \Log::debug('PSR parsed body', (array) $psrRequest->getParsedBody());

        try {
            return parent::issueToken($psrRequest, new Psr7Response());
        } catch (OAuthServerException $e) {
            return response()->json([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
