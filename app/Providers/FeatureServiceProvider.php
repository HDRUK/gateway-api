<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use App\FeatureFlags\FeatureFlagManager;

class FeatureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeatureFlagManager::class, function ($app) {
            return new FeatureFlagManager();
        });
    }

    public function boot(): void
    {
        Feature::resolveScopeUsing(
            $this->app->make(FeatureFlagManager::class)
        );
    }
}
