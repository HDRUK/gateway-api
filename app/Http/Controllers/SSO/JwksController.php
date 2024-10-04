<?php

namespace App\Http\Controllers\SSO;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class JwksController extends Controller
{
    public function getJwks(Request $request)
    {
        $publicKeyContent = str_replace('\\n', "\n", env('PASSPORT_PUBLIC_KEY'));
        if (empty($publicKeyContent)) {
            throw new Exception('Public key not found in environment variables');
        }

        $details = openssl_pkey_get_details(openssl_pkey_get_public($publicKeyContent));

        $keys = [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'n'   => strtr(rtrim(base64_encode($details['rsa']['n']), '='), '+/', '-_'),
            'e'   => strtr(rtrim(base64_encode($details['rsa']['e']), '='), '+/', '-_'),
            'kid' => env('JWT_KID', 'jwtkidnotfound'),
        ];

        // we can generate for server
        // $keys['kid'] = bin2hex(random_bytes(16));

        $jwks = [
            'keys' => [
                $keys
            ]
        ];

        return response()->json(
            $jwks
        );
    }
}
