<?php

namespace App\Providers;

use App\EnquiriesManagementController\EnquiriesManagementController;
use Illuminate\Support\ServiceProvider;

class EnquiriesManagementControllerServiceProvider extends ServiceProvider
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
        $this->app->bind('enquiriesmanagementcontroller', function () {
            return new EnquiriesManagementController();
        });
    }
}
