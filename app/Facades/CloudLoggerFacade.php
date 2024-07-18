<?php

namespace App\Facades;

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