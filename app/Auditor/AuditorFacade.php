<?php

namespace App\Auditor;

use Illuminate\Support\Facades\Facade;

class AuditorFacade extends Facade {
    protected static function getFacadeAccessor(): string
    {
        return 'auditor';
    }
}