<?php

namespace App\Providers;

use Laravel\Pennant\Feature;
use Illuminate\Support\ServiceProvider;

class AppFeatureServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Feature::resolveScopeUsing(fn ($driver) => null);

    }
}
