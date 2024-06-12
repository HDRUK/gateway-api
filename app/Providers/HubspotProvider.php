<?php

namespace App\Providers;

use App\Services\Hubspot;
use Illuminate\Support\ServiceProvider;

class HubspotProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(Hubspot::class, function ($app) {
            return new Hubspot();
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