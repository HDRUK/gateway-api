<?php

namespace App\Providers;

use App\Auditor\Auditor;
use App\Services\CloudLoggerService;
use App\Services\CloudPubSubService;
use Illuminate\Support\ServiceProvider;

class AuditorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(CloudLoggerService::class, function ($app) {
            return new CloudLoggerService();
        });

        $this->app->bind(CloudPubSubService::class, function ($app) {
            return new CloudPubSubService();
        });

        $this->app->bind('auditor', function($app) {
            return new Auditor(
                $app->make(CloudLoggerService::class),
                $app->make(CloudPubSubService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     * 
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
