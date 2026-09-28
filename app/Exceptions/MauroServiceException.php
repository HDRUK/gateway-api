<?php

namespace App\Exceptions;

use Exception;

class MauroServiceException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-MAURO-SERVICE-';

    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }
}
