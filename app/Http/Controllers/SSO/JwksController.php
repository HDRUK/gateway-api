<?php

namespace App\Http\Controllers\SSO;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class JwksController extends Controller
{
    public function getJwks(Request $request)
    {
        // remove after
        // $jwt = (new Parser())->parse((string) $token);
        // $kid = $jwt->headers()->get('kid');
        // $path = file_get_contents(__DIR__ . '/../../../storage/oauth-private.key');
        // $details = openssl_pkey_get_details(openssl_pkey_get_private($path));

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
            'n2' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($details['rsa']['n'])), '='),
            'e2' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($details['rsa']['e'])), '='),
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
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
