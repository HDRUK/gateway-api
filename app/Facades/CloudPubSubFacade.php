<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CloudPubSubFacade
 */
class CloudPubSubFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cloudpubsub';
    }
}