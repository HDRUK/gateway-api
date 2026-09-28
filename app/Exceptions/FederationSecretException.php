<?php

namespace App\Exceptions;

use Exception;

class FederationSecretException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-FEDERATION-SECRET-';

    public function __construct(
        string $message,
        private readonly string $details = '',
    ) {
        parent::__construct($message);
    }

    public function getDetails(): string
    {
        return $this->details;
    }
}
