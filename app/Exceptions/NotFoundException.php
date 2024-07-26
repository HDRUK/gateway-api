<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class NotFoundException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = Response::HTTP_NOT_FOUND,
        Exception $previous = null,
    ) 
    {
        $message = $message ? $message : Response::$statusTexts[Response::HTTP_NOT_FOUND];

        parent::__construct($message, $code, $previous);
    }
}
