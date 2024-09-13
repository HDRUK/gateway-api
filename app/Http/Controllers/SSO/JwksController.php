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
            // 'n2' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($details['rsa']['n'])), '='),
            // 'e2' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($details['rsa']['e'])), '='),
            // 'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            // 'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
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

    // public function getJwks(Request $request)
    // {
    //     // Load the public key from storage
    //     $publicKeyPath = storage_path('oauth-public.key');
    //     if (!file_exists($publicKeyPath)) {
    //         throw new Exception('File not found');
    //     }

    //     // Read the public key content
    //     $publicKey = file_get_contents($publicKeyPath);
    //     if ($publicKey === false) {
    //         throw new Exception('Unable to read public key.');
    //     }

    //     // $pem = str_replace(["-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\n"], '', $publicKey);

    //     try {
    //         $der = base64_decode($publicKey);
    //         $details = openssl_pkey_get_details(openssl_pkey_get_public($der));
    //         $n = $details['rsa']['n'];
    //         $n = rtrim(strtr(base64_encode($n), '+/', '-_'), '=');
    //         $e = $details['rsa']['e'];
    //         $e = rtrim(strtr(base64_encode($e), '+/', '-_'), '=');

    //         $jwks = [
    //             'keys' => [
    //                 'kty' => 'RSA',
    //                 'kid' => env('JWT_KID', 'jwtkidnotfound'),
    //                 'use' => 'sig',
    //                 'alg' => 'RS256',
    //                 'n' => $n,
    //                 'e' => $e,
    //             ]
    //         ];

    //         return response()->json(
    //             $jwks
    //         );
    //     } catch (\Exception $e) {
    //         throw new Exception('Failed to create JWK :: ' . $e->getMessage());
    //     }
    // }
}
