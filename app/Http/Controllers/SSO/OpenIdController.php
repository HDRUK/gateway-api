<?php

namespace App\Http\Controllers\SSO;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OpenIdController extends Controller
{
    protected $scopes_supported = [
        'openid',
        'email',
        'profile',
        'rquestroles',
    ];
    protected $response_types_supported = [
        'code',
        'none',
        'id_token',
        'access_token',
        'id_token access_token',
        'code id_token',
        'code access_token',
        'code id_token access_token',
    ];
    protected $grant_types_supported = [
        'authorization_code',
        'implicit',
        'refresh_token',
        'password',
        'client_credentials',
        'urn:ietf:params:oauth:grant-type:device_code',
        'urn:openid:params:grant-type:ciba',
    ];
    protected $subject_types_supported = ['public'];
    protected $id_token_signing_alg_values_supported = [
        'RS256',
    ];

    public function getOpenIdConfiguration(Request $request)
    {
        $config = [
            // Required
            'issuer'                    => env('APP_URL'),
            'authorization_endpoint'    => env('APP_URL') . '/oauth/authorize',
            'token_endpoint'            => env('APP_URL') . '/oauth/token',
            'token_refresh_endpoint'    => env('APP_URL') . '/oauth/token/refresh',
            'jwks_uri'                  => env('APP_URL') . '/oauth/.well-known/jwks',
            'response_types_supported'  => $this->response_types_supported,
            'subject_types_supported'   => $this->subject_types_supported,
            'id_token_signing_alg_values_supported' => $this->id_token_signing_alg_values_supported,
            'userinfo_signing_alg_values_supported' => $this->id_token_signing_alg_values_supported,

            // Recommended
            'userinfo_endpoint'         => env('APP_URL') . '/api/oauth/userinfo',
            'registration_endpoint'     => '',
            'scopes_supported'          => $this->scopes_supported,
            'grant_types_supported'     => $this->grant_types_supported,
            'claims_supported'          => [
                "aud",
                "sub",
                "iss",
                "auth_time",
                "name",
                "given_name",
                "family_name",
                "preferred_username",
                "email",
                "acr",
                "firstname",
                "lastname",
                "rquestroles",
                "ga4gh_passport_v1",
                "sid",
            ],

            // logme out
            'revocation_endpoint'       => env('APP_URL') . '/api/oauth/logmeout',

            'token_endpoint_auth_methods_supported' => [
                'none',
                'client_secret_basic',
                'client_secret_jwt',
                'client_secret_post',
                'private_key_jwt'
            ],

            'acr_values_supported' => [
                0,
                1,
            ]
        ];

        return response()->json($config);
    }
}
