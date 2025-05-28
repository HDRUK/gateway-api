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

            $featureFlags = Cache::remember('feature_flags', now()->addMinutes(10), function () use ($url) {
                logger()->info('Calling that Bucket');
                $res = Http::retry(3, 5000, function ($exception, $requestNumber) use ($url) {
                    logger()->warning('Retrying feature flag fetch', [
                        'url' => $url,
                        'attempt' => $requestNumber,
                        'error' => $exception->getMessage(),
                    ]);
                })->get($url);

                if (!$res->successful()) {
                    logger()->error('Failed to fetch feature flags', ['url' => $url, 'status' => $res->status()]);
                }

                return $res->successful() ? $res->json() : [];
            });

            if (is_array($featureFlags) && !empty($featureFlags)) {
                app(FeatureFlagManager::class)->define($featureFlags);
            } else {
                logger()->warning('No feature flags were defined - empty or failed response.', ['url' => $url]);
            }
        });
    }


}
