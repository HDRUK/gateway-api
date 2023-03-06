<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;

class JwtController extends Controller
{
    /**
     * @param string $secretKey
     */

    private string $secretKey;

    /**
     * @param array $header
     */
    private array $header;

    /**
     * @param array $payload
     */
    private array $payload;

    /**
     * @param string $jwt
     */
    private string $jwt;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->header = [
            "alg" => "HS256",
            "typ" => "JWT"
        ];
        $this->payload = [];
        $this->jwt = '';
        $this->secretKey = (string) env('JWT_SECRET');
    }

    /**
     * set header
     * 
     * @param array $value
     * @return array
     */
    public function setHeader(array $value): array
    {
        return $this->header = $value;
    }

    /**
     * set payload
     * 
     * @param array $value
     * @return array
     */
    public function setPayload(array $value): array
    {
        return $this->payload = $value;
    }

    /**
     * set jwt value
     * 
     * @param string $value
     * @return string
     */
    public function setJwt(string $value): string
    {
        return $this->jwt = $value;
    }

    /**
     * create the JWT token
     * 
     * @return string
     */
    public function create(): string
    {
        $headerEncoded = $this->base64UrlEncode(json_encode($this->header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($this->payload));

        $signature = hash_hmac('SHA256', "$headerEncoded.$payloadEncoded", $this->secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        $jwt = "$headerEncoded.$payloadEncoded.$signatureEncoded";

        return $jwt;
    }

    /**
     * check if JWT token is valid
     * 
     * @return boolean
     */
    public function isValid()
    {
        $tokenParts = explode('.', $this->jwt);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

        $expiration = json_decode($payload)->exp;
        $isTokenExpired = ($expiration - time()) < 0;

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('SHA256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        $isSignatureValid = ($base64UrlSignature === $signatureProvided);

        if ($isTokenExpired || !$isSignatureValid) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * encode value in base64
     * 
     * @param string $value
     * @return string
     */
    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

}
