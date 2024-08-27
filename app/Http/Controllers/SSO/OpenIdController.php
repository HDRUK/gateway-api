<?php

namespace App\Http\Controllers\SSO;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OpenIdController extends Controller
{
    protected $scopes_supported = [
        'openid',
        'offline_access',
        'email',
        'profile',
        'rquestroles',
        'ga4gh_passport_v1'
    ];
    protected $response_types_supported = [
        'code',
        'none',
        'id_token',
        'token',
        'id_token token',
        'code id_token',
        'code token',
        'code id_token token',
    ];
    protected $grant_types_supported = [
        'implicit',
        'authorization_code',
        'refresh_token',
        'urn:ietf:params:oauth:grant-type:device_code'
    ];
    protected $subject_types_supported = ['public'];
    protected $id_token_signing_alg_values_supported = [
        'RS256'
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

            // Recommended
            'userinfo_endpoint'         => env('APP_URL') . '/oauth/userinfo',
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
            ],

            // logme out
            'revocation_endpoint'       => env('APP_URL') . '/oauth/logmeout',

            'token_endpoint_auth_methods_supported' => [
                'none',
                'client_secret_basic',
                'client_secret_jwt',
                'client_secret_post',
                'private_key_jwt'
            ],
        ];

        return response()->json($config);
    }
}
