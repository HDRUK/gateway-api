<?php

namespace App\Auditor;

use App\Auditor\Auditor;
use Illuminate\Support\Facades\Facade;

class AuditorFacade extends Facade {

    protected static function getFacadeAccessor()
    {
        return 'auditor';
    }
}