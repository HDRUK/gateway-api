<?php

namespace App\Providers;

use App\Services\ElasticClientControllerService;
use Illuminate\Support\ServiceProvider;

class ElasticClientControllerServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ElasticClientControllerService::class, function ($app) {
            return new ElasticClientControllerService();
        });
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
