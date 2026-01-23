<?php

namespace App\Http\Controllers\SSO;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportAccessTokenController;
use Illuminate\Http\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use League\OAuth2\Server\Exception\OAuthServerException;

use League\OAuth2\Server\Exception\OAuthServerException as LeagueOAuthException;
use Laravel\Passport\Exceptions\OAuthServerException as PassportOAuthException;


class CustomAccessTokenController extends PassportAccessTokenController
{
    public function issueOAuthToken(Request $request)
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            return parent::issueToken($psrRequest);
        } catch (LeagueOAuthException $e) {
            return response()->json([
                'error'   => $e->getErrorType(),
                'message' => $e->getMessage(),
                'hint'    => method_exists($e, 'getHint') ? $e->getHint() : null,
            ], $e->getHttpStatusCode());
        } catch (PassportOAuthException $e) {
            $prev = $e->getPrevious();
            if ($prev instanceof LeagueOAuthException) {
                return response()->json([
                    'error'   => $prev->getErrorType(),
                    'message' => $prev->getMessage(),
                    'hint'    => method_exists($prev, 'getHint') ? $prev->getHint() : null,
                ], $prev->getHttpStatusCode());
            }

            // Fallback
            return response()->json([
                'error'   => 'oauth_error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
