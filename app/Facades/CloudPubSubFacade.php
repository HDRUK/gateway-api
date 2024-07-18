<?php

namespace App\Facades;

use App\Services\CloudPubSubService;
use Illuminate\Support\Facades\Facade;

/**
 * CloudPubSubFacade
 */
class CloudPubSubFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return CloudPubSubService::class;
    }
}