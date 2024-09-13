<?php

namespace App\Http\Controllers\SSO;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class JwksController extends Controller
{
    public function getJwks(Request $request)
    {
        $filePath = storage_path('oauth-public.key');
        if (!file_exists($filePath)) {
            throw new Exception('File not found');
        }

        $path = file_get_contents($filePath);
        $details = openssl_pkey_get_details(openssl_pkey_get_public($path));

        $keys = [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'n'   => strtr(rtrim(base64_encode($details['rsa']['n']), '='), '+/', '-_'),
            'e'   => strtr(rtrim(base64_encode($details['rsa']['e']), '='), '+/', '-_'),
            'kid' => env('JWT_KID', 'jwtkidnotfound'),
        ];

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
