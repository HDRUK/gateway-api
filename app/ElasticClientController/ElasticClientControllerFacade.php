<?php

namespace App\ElasticClientController;

use Illuminate\Support\Facades\Facade;

class ElasticClientControllerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'elasticclientcontroller';
    }
}
