<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    // App\Providers\BroadcastServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\AuditorServiceProvider::class,
    App\Providers\MetadataManagementControllerServiceProvider::class,
    App\Providers\AliasReplyScannerProvider::class,
    Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
    App\Providers\CloudLoggerProvider::class,
    App\Providers\CloudPubSubProvider::class,
    App\Providers\ElasticClientControllerServiceProvider::class,
    App\Providers\TypesenseServiceProvider::class,
    App\Providers\HelperServiceProvider::class,
    App\Providers\AppFeatureServiceProvider::class,
    App\Providers\SearchServiceProvider::class,
    App\Providers\ExceptionProfileServiceProvider::class,
];
