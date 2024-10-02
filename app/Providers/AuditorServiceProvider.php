<?php

namespace App\Providers;

use App\Auditor\Auditor;
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
        $this->app->bind('auditor', function () {
            return new Auditor();
        });
    }
}
