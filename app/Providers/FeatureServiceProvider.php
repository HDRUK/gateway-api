<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use App\Services\FeatureFlagManager;

class FeatureServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (Feature::defined()->isNotEmpty()) {
            return;
        }
        $url = env('FEATURE_FLAGGING_CONFIG_URL');

        if (app()->environment('testing') || !$url) {
            return;
        }

        $res = Http::get($url);

        if ($res->successful()) {
            $featureFlags = $res->json();
            if (is_array($featureFlags)) {
                $flagManager->define($featureFlags);
            }
        } else {
            logger()->error('Failed to fetch feature flags from URL', ['url' => $url]);
        }

        if (is_array($featureFlags)) {
            $flagManager->define($featureFlags);
        }
    }

    
}
