<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class ResourceAlreadyExistsException extends Exception
{
    public function __construct(
        string $message = 'Resource already exists',
        int $code = Response::HTTP_CONFLICT,
        ?Exception $previous = null,
    ) {
        $message = $message ? $message : Response::$statusTexts[Response::HTTP_CONFLICT];

        parent::__construct($message, $code, $previous);
    }
}
