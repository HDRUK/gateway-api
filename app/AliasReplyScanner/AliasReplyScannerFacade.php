<?php

namespace App\AliasReplyScanner;

use Illuminate\Support\Facades\Facade;

class AliasReplyScannerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'aliasreplyscanner';
    }
}
