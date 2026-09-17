<?php

namespace App\Exceptions;

use Exception;

class EmailTemplateException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-EMAIL-TEMPLATE-';
}
