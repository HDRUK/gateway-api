<?php

namespace App\EnquiriesManagementController;

use Illuminate\Support\Facades\Facade;

class EnquiriesManagementControllerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'enquiriesmanagementcontroller';
    }
}
