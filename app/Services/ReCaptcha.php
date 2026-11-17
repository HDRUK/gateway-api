<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ReCaptcha
{
    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public const ACTION_EXPERIMENT_RESPONSE = 'experiment_response';

    private const SECRET_ERRORS = ['missing-input-secret', 'invalid-input-secret'];

    public function verify(string $token, string $action): bool
    {
        $secret = config('services.recaptcha.secret_key');
        $minScore = (float)config('services.recaptcha.min_score');

        if (!filled($secret) || $minScore <= 0) {
            \Log::warning('reCAPTCHA is not configured, so the token was rejected');

            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => $secret,
                'response' => $token,
            ]);
        } catch (ConnectionException $e) {
            \Log::warning('reCAPTCHA verification failed', ['error' => $e->getMessage()]);

            return false;
        }

        if (!$response->successful()) {
            \Log::warning('reCAPTCHA verification failed', ['status' => $response->status()]);

            return false;
        }

        $errors = (array)$response->json('error-codes', []);

        if (array_intersect($errors, self::SECRET_ERRORS)) {
            \Log::warning('reCAPTCHA rejected the secret key', ['error_codes' => $errors]);

            return false;
        }

        $passed = $response->json('success') === true &&
            $response->json('action') === $action &&
            (float)$response->json('score', 0) >= $minScore;

        if (!$passed) {
            \Log::debug('reCAPTCHA rejected the token', [
                'success' => $response->json('success'),
                'error_codes' => $errors,
                'score' => $response->json('score'),
                'action' => $response->json('action'),
                'hostname' => $response->json('hostname'),
            ]);
        }

        return $passed;
    }
}
