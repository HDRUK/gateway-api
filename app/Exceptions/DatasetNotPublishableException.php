<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class DatasetNotPublishableException extends Exception
{
    public function __construct(
        string $message = 'Dataset metadata is not valid for publishing',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
