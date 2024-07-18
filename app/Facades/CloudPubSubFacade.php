<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CloudLoggerFacade
 */
class CloudPubSubFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cloudpubsub';
    }
}