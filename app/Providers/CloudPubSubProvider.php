<?php

namespace App\Providers;

use App\Services\CloudPubSubService;
use Illuminate\Support\ServiceProvider;

class CloudPubSubProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('cloudpubsub', function ($app) {
            return new CloudPubSubService();
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
