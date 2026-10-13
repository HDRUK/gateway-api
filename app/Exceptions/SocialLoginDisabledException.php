<?php

namespace App\Exceptions;

use Exception;

/**
 * Raised when a sign-in is attempted through a provider that is currently
 * switched off - either by its own feature flag or by the global
 * `LoginDisabled` kill switch.
 *
 * Carries its own redirect so the browser lands on a "temporarily
 * unavailable" page rather than the generic 500 the catch-all would give,
 * and so the Gateway and DTA front ends each get their own.
 */
class SocialLoginDisabledException extends Exception
{
    private string $redirectUrl;

    public function __construct(string $redirectUrl, string $message)
    {
        parent::__construct($message);

        $this->redirectUrl = $redirectUrl;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }
}
