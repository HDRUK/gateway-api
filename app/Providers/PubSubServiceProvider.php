<?php

namespace App\Providers;

use App\Services\PubSubService;
use Illuminate\Support\ServiceProvider;

class PubSubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PubSubService::class, function ($app) {
            return new PubSubService();
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
