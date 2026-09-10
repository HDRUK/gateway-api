<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class BadRequestException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-BAD-REQUEST-';

    public function __construct(
        string $message = '',
        int $code = Response::HTTP_BAD_REQUEST,
        ?Exception $previous = null,
    ) {
        $message = $message ? $message : Response::$statusTexts[Response::HTTP_BAD_REQUEST];

        parent::__construct($message, $code, $previous);
    }
}
