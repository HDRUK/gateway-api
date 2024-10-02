<?php

namespace App\Providers;

use App\Mauro\Mauro;
use Illuminate\Support\ServiceProvider;

class MauroServiceProvider extends ServiceProvider
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
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind('mauro', function () {
            return new Mauro();
        });
    }
}
