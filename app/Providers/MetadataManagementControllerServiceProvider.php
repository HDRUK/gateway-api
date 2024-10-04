<?php

namespace App\Providers;

use App\MetadataManagementController\MetadataManagementController;
use Illuminate\Support\ServiceProvider;

class MetadataManagementControllerServiceProvider extends ServiceProvider
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
        $this->app->bind('metadatamanagementcontroller', function () {
            return new MetadataManagementController();
        });
    }
}
