<?php

namespace App\Facades;

use App\Services\CloudPubSubService;
use Illuminate\Support\Facades\Facade;

class CloudPubSubFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CloudPubSubService::class;
    }
}