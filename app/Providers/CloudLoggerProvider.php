<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CloudLoggerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('cloudlogger', function ($app) {
            return new \App\Services\CloudLoggerService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
