<?php

namespace App\Facades;

use App\Services\ElasticClientControllerService;
use Illuminate\Support\Facades\Facade;

class ElasticClientControllerFacade extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return ElasticClientControllerService::class;
    }
}
