<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class InternalServerErrorException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?Exception $previous = null,
    ) {
        $message = $message ? $message : Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];

        parent::__construct($message, $code, $previous);
    }
}
