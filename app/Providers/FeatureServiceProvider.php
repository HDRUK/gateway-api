<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use App\Services\FeatureFlagManager;
use Illuminate\Support\Facades\Cache;

class FeatureServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->booted(function () {
            logger()->info('Starting features');
            $url = env('FEATURE_FLAGGING_CONFIG_URL');

            if (app()->environment('testing') || !$url) {
                return;
            }

            $featureFlags = Cache::remember('feature_flags', now()->addMinutes(60), function () use ($url) {
                $res = Http::retry(3, 5000)->get($url);
                logger()->info('Called Bucket');

                return $res->successful() ? $res->json() : [];
            });

            if (is_array($featureFlags)) {
                app(FeatureFlagManager::class)->define($featureFlags);
            }
        });
    }


}
