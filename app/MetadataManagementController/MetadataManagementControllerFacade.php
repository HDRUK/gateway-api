<?php

namespace App\MetadataManagementController;

use Illuminate\Support\Facades\Facade;

class MetadataManagementControllerFacade extends Facade {
    protected static function getFacadeAccessor(): string
    {
        return 'metadatamanagementcontroller';
    }
}