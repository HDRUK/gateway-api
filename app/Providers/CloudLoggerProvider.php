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
        $this->app->singleton(CloudLoggerService::class, function ($app) {
            return new CloudLoggerService();
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
