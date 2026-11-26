<?php

namespace App\Modules\Closer;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Thin client for CLOSER Discovery's Colectica Repository REST API.
 *
 * The token endpoint sits at the site root ({base_url}/token/createtoken);
 * every data endpoint sits under {base_url}/api/v1. Tokens are cached until
 * shortly before they expire and replaced once if CLOSER rejects them.
 */
class CloserClient
{
    public const TOKEN_CACHE_KEY = 'closer.api.token';

    private const TOKEN_PATH = '/token/createtoken';

    private const API_PREFIX = '/api/v1/';

    /** Refresh this many seconds before CLOSER says the token expires. */
    private const EXPIRY_MARGIN = 60;

    /** Used when CLOSER's `expires` value can't be read. */
    private const FALLBACK_TTL = 600;

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.closer.base_url'), '/');
    }

    /** GET a path under /api/v1, e.g. "item/{agency}/{id}". */
    public function get(string $path, array $query = []): Response
    {
        $response = $this->api()->get(ltrim($path, '/'), $query);

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->api()->get(ltrim($path, '/'), $query);
        }

        return $response;
    }

    /**
     * Accepts "agency/id", "urn:ddi:agency:id[:version]" or a Discovery
     * item URL ending in /item/{agency}/{id}.
     *
     * @return array{agency: string, id: string, version: ?int}
     */
    public static function parseIdentifier(string $input): array
    {
        $input = trim($input);

        if (preg_match('/^urn:ddi:([^:]+):([^:]+)(?::(\d+))?$/i', $input, $m)) {
            return ['agency' => $m[1], 'id' => $m[2], 'version' => isset($m[3]) ? (int) $m[3] : null];
        }

        if (preg_match('#(?:/item/)?([^/\s]+\.[^/\s]+)/([^/\s]+)/?$#i', $input, $m)) {
            return ['agency' => $m[1], 'id' => $m[2], 'version' => null];
        }

        throw new InvalidArgumentException(
            "Can't find an agency and identifier in '{$input}'. Use agency/id, a DDI URN, or a Discovery item URL."
        );
    }

    private function api(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl($this->baseUrl.self::API_PREFIX)
            ->withToken($this->token())
            ->timeout(30);
    }

    private function token(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $username = config('services.closer.username');
        $password = config('services.closer.password');
        if (empty($username) || empty($password)) {
            throw new RuntimeException('CLOSER_API_USERNAME and CLOSER_API_PASSWORD must be set.');
        }

        $response = Http::acceptJson()->timeout(30)->post($this->baseUrl.self::TOKEN_PATH, [
            'username' => $username,
            'password' => $password,
        ]);

        $token = $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new RuntimeException("CLOSER token request failed: HTTP {$response->status()}.");
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, $this->ttl($response->json('expires')));

        return $token;
    }

    /** CLOSER's `expires` may be seconds or a timestamp; neither is documented. */
    private function ttl(mixed $expires): int
    {
        $seconds = self::FALLBACK_TTL;

        if (is_numeric($expires)) {
            $seconds = (int) $expires;
        } elseif (is_string($expires)) {
            try {
                $seconds = (int) CarbonImmutable::now()->diffInSeconds(CarbonImmutable::parse($expires), false);
            } catch (Throwable) {
                // Keep the fallback.
            }
        }

        return max(30, $seconds - self::EXPIRY_MARGIN);
    }
}
