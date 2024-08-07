<?php

namespace App\Providers;

use App\ElasticClientController\ElasticClientController;

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
        //
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind('elasticclientcontroller', function () {
            return new ElasticClientController();
        });
    }
}
