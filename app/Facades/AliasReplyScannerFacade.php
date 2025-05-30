<?php

namespace App\Facades;

use App\Services\AliasReplyScannerService;
use Illuminate\Support\Facades\Facade;

/**
 * AliasReplyScannerFacade
 */
class AliasReplyScannerFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return AliasReplyScannerService::class;
    }
}
