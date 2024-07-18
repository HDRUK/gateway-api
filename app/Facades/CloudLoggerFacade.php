<?php

namespace App\Facades;

use App\Services\CloudLoggerService;
use Illuminate\Support\Facades\Facade;

/**
 * CloudLoggerFacade
 */
class CloudLoggerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cloudlogger';
    }
}