<?php

namespace App\Exceptions;

use Exception;

class SocialLoginConflictException extends Exception
{
    private string $redirectUrl;

    public function __construct(string $redirectUrl)
    {
        parent::__construct('An account with this email already exists under a different sign-in method');

        $this->redirectUrl = $redirectUrl;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }
}
