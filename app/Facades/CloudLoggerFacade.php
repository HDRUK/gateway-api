<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CloudLoggerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cloudlogger';
    }
}