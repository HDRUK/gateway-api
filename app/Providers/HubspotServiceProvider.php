<?php

namespace App\Providers;

use App\Services\HubspotService;
use Illuminate\Support\ServiceProvider;

class HubspotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(HubspotService::class, function ($app) {
            return new HubspotService();
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