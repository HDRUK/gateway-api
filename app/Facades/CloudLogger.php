<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CloudLogger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cloudlogger';
    }
}