<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\JwtController;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;

/**
 * Bridges Passport's OAuth2 token endpoint onto our own token format.
 *
 * POST /oauth2/token completes the OAuth2 flow and would return an RS256
 * access token. This swaps that response for an ordinary Gateway HS256
 * token for the same subject, so a client finishing the OAuth2 dance ends
 * up holding the credential the rest of the API understands.
 *
 * The incoming token is parsed but NOT signature-verified. That is sound
 * only because the token being read is the response this application
 * generated moments earlier in the same request; it is never client
 * input. Any change that lets an externally supplied token reach this
 * path turns it into account takeover.
 *
 * @see \Tests\Feature\Auth\TokenBridgeCharacterisationTest
 */
class AppendJWTTokenResponse
{
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

        $content = json_decode($response->getContent(), true);

        if (!isset($content['access_token'])) {
            return $response;
        }

        // A bare parser, because only the claims are wanted. The previous
        // version built a SYMMETRIC HS256 configuration to read what is an
        // RS256 token, which worked solely because parse() ignores the key
        // - it read as though the wrong key were being used to verify.
        $token = (new Parser(new JoseEncoder()))->parse($content['access_token']);

        if (!$token instanceof UnencryptedToken) {
            return $response;
        }

        $subject = $token->claims()->get('sub');

        return response()->json([
            'token' => (new JwtController())->generateToken($subject),
        ], $response->getStatusCode(), $response->headers->all());
    }
}
