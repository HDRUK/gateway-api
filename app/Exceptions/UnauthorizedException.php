<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class UnauthorizedException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = Response::HTTP_UNAUTHORIZED,
        ?Exception $previous = null,
    ) {
        $message = $message ? $message : Response::$statusTexts[Response::HTTP_UNAUTHORIZED];

        parent::__construct($message, $code, $previous);
    }
}
