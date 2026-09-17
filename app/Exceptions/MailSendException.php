<?php

namespace App\Exceptions;

use Exception;

class MailSendException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-MAIL-SEND-';
}
