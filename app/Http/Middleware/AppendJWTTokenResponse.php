<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\RedirectResponse;

use App\Http\Controllers\JwtController;
use App\Models\User;
use App\Http\Traits\CustomIdTokenTrait;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Signer\Key\InMemory;

class AppendJWTTokenResponse
{
    use CustomIdTokenTrait;

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response|JsonResponse
     */
    public function handle(Request $request, Closure $next): RedirectResponse|Response|JsonResponse
    {
        $response = $next($request);
        $currentUrl = $request->url();

        $content = json_decode($response->getContent(), true);
        if (isset($content['access_token'])) {

            $signer = new Sha256();
            $key = InMemory::plainText(env('JWT_SECRET'));

            // Configure the parser. No validation needed, just parsing.
            $config = Configuration::forSymmetricSigner($signer, $key);
            $token = $config->parser()->parse($content['access_token']);
            
            $jwtClass = new JwtController();
            $jwt = $jwtClass->generateToken($token->claims()->get('sub'));

            return response()->json([
                'token' => $jwt,
            ], $response->getStatusCode(), $response->headers->all());
        }

        return $response;
    }
}
