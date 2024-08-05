<?php

namespace App\Mauro;

use Illuminate\Support\Facades\Facade;

class MauroFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mauro';
    }
}
