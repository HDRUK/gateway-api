<?php

namespace App\Facades;

use App\Services\ElasticClientControllerService;
use Illuminate\Support\Facades\Facade;

class ElasticClientControllerFacade extends Facade
{
    public const ELASTIC_NAME_DATASET               = 'dataset';
    public const ELASTIC_NAME_TOOL                  = 'tool';
    public const ELASTIC_NAME_PUBLICATION           = 'publication';
    public const ELASTIC_NAME_COLLECTION            = 'collection';
    public const ELASTIC_NAME_DUR                   = 'datauseregister';
    public const ELASTIC_NAME_DATAPROVIDER          = 'dataprovider';
    public const ELASTIC_NAME_DATAPROVIDERCOLL      = 'dataprovidercoll';
    public const ELASTIC_NAME_DATACUSTODIANNETWORK   = 'datacustodiannetwork';

    public static function getFacadeAccessor(): string
    {
        return ElasticClientControllerService::class;
    }
}
