<?php

namespace App\Http\Controllers\SSO;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use Illuminate\Http\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\Response;

class CustomAccessTokenController extends PassportAccessTokenController
{
    public function issueOAuthToken(Request $request)
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory(
            $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory
        );

        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            return parent::issueToken($psrRequest);
        } catch (OAuthServerException $e) {
            return response()->json([
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
