<?php

namespace App\Providers;

use App\Services\CloudPubSubService;
use Illuminate\Support\ServiceProvider;

class CloudPubSubProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the CloudLoggerService class to the service container
        $this->app->singleton(CloudPubSubService::class, function ($app) {
            return new CloudPubSubService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
