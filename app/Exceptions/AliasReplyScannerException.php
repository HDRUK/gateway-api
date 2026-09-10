<?php

namespace App\Exceptions;

use Exception;

class AliasReplyScannerException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-ARS-';

    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }
}
