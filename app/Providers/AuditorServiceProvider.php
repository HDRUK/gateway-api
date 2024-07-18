<?php

namespace App\Providers;

use App\Services\AuditorService;
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
        // Bind the CloudLoggerService class to the service container
        $this->app->singleton('auditor', function ($app) {
            return new AuditorService();
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
