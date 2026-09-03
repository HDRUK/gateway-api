<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class UnprocessableException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?Exception $previous = null,
    ) {
        $message = $message ? $message : Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY];

        parent::__construct($message, $code, $previous);
    }
}
