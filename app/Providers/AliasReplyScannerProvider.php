<?php

namespace App\Providers;

use App\Services\AliasReplyScannerService;
use Illuminate\Support\ServiceProvider;

class AliasReplyScannerProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(AliasReplyScannerService::class, function ($app) {
            return new AliasReplyScannerService();
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
