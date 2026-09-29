<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class FederationAlreadyRunningException extends Exception
{
    public const ERROR_BOUNDS = 'ERR-FEDERATION-ALREADY-RUNNING-';

    public function __construct(
        string $message = 'Federation is already running an integration synchronisation.',
        int $code = Response::HTTP_CONFLICT,
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
