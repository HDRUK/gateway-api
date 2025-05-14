<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use App\Services\FeatureFlagManager;
use Illuminate\Support\Facades\Cache;

class FeatureServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (count(Feature::defined()) > 0) {
            return;
        }

        //$url = env('FEATURE_FLAGGING_CONFIG_URL');
        $url = "https://raw.githubusercontent.com/HDRUK/hdruk-feature-configurations/refs/heads/feat/GAT-6927-2/dev/features.json";
        if (app()->environment('testing') || !$url) {
            return;
        }

        $featureFlags = Cache::remember('feature_flags', now()->addMinutes(60), function () use ($url) {
            $res = Http::get($url);
            if ($res->successful()) {
                return $res->json();
            }

            logger()->error('Failed to fetch feature flags from URL', ['url' => $url]);
            return [];
        });

        if (is_array($featureFlags)) {
            $flagManager = app(FeatureFlagManager::class);
            $flagManager->define($featureFlags);
        }


    }


}
