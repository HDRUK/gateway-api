<?php

namespace App\Facades;

use App\Services\AuditorService;
use Illuminate\Support\Facades\Facade;

/**
 * AuditorFacade
 */
class AuditorFacade extends Facade {

    protected static function getFacadeAccessor()
    {
        return AuditorService::class;
    }
}