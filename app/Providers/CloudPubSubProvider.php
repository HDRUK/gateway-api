<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CloudPubSubProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('cloudpubsub', function ($app) {
            return new \App\Services\CloudPubSubService;
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
