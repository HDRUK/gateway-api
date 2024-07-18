<?php

namespace App\Facades;

use App\Services\CloudPubSubService;
use Illuminate\Support\Facades\Facade;

class CloudPubSub extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cloudpubsub';
    }
}