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
        //
    }

    /**
     * Bootstrap services.
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->app->singleton('auditor', function ($app) {
            return new AuditorService();
        });
    }
}
