<?php

namespace App\Exceptions;

use Exception;

class MMCException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-MMC-';

    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
