<?php

namespace App\Http\Controllers\SSO;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response as Psr7Response;

class CustomAccessTokenController extends PassportAccessTokenController
{
    public function issueOAuthToken(ServerRequestInterface $psrRequest)
    {
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
