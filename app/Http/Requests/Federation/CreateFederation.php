<?php

namespace App\Http\Requests\Federation;

use Closure;
use App\Http\Requests\BaseFormRequest;

class CreateFederation extends BaseFormRequest
{
    /**
     * CIDR ranges that must never be the target of a federation fetch.
     * Covers loopback, link-local (cloud metadata), and RFC-1918 private ranges.
     */
    private const BLOCKED_CIDRS = [
        '127.0.0.0/8',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '169.254.0.0/16', // AWS/GCP/Azure instance metadata
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    ];

    /**
     * Returns true when $ip falls within the given CIDR block.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        if (str_contains($cidr, ':')) {
            // IPv6 — skip detailed check for non-IPv6 addresses
            return false;
        }
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = $bits === '0' ? 0 : (~0 << (32 - (int)$bits));
        return ($ip & $mask) === ($subnet & $mask);
    }

    private function isBlockedUrl(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return true;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return true;
        }

        $host = $parsed['host'];

        // Reject bare IPs that look internal without needing DNS
        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip === false) {
            // Resolve hostname — if it fails, block the request
            $ip = gethostbyname($host);
            if ($ip === $host) {
                // gethostbyname returns the input unchanged on failure
                return true;
            }
        }

        foreach (self::BLOCKED_CIDRS as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => [
                'required',
                'int',
                'exists:teams,id',
            ],
            'federation_type' => [
                'required',
                'string',
            ],
            'auth_type' => [
                'required',
                'string',
                'in:API_KEY,BEARER,NO_AUTH',
            ],
            'auth_secret_key' => [
                'string',
            ],
            'endpoint_baseurl' => [
                'required',
                'string',
                'url',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($this->isBlockedUrl($value)) {
                        $fail("The {$attribute} must not target an internal or reserved address.");
                    }
                },
            ],
            'endpoint_datasets' => [
                'required',
                'string',
            ],
            'endpoint_dataset' => [
                'required',
                'string',
            ],
            'run_time_hour' => [
                'required',
                'int',
                'between:0,23',
            ],
            'enabled' => [
                'required',
                'boolean',
            ],
            'notifications' => [
                'array',
            ],
            'notifications.*' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    if (!(is_numeric($value) || filter_var($value, FILTER_VALIDATE_EMAIL))) {
                        $fail("The {$attribute} is invalid.");
                    }
                },
            ],
            'tested' => [
                'boolean',
            ],
        ];
    }

    /**
     * Add Route parameters to the FormRequest.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(['team_id' => $this->route('teamId')]);
    }
}
