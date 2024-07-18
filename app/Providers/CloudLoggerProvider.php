<?php

namespace App\Providers;

use App\Services\CloudLoggerService;
use Illuminate\Support\ServiceProvider;

class CloudLoggerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->singleton('cloudlogger', function ($app) {
            return new CloudLoggerService();
        });
    }
}
