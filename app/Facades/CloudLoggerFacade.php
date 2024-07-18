<?php

namespace App\Facades;

use App\Services\CloudLoggerService;
use Illuminate\Support\Facades\Facade;

/**
 * CloudLoggerFacade
 */
class CloudLoggerFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return CloudLoggerService::class;
    }
    
}