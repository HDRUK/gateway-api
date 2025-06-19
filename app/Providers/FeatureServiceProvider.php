<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use App\Services\FeatureFlagManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Contracts\Cache\LockTimeoutException;

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

            // Try to load feature flags from cache
            $featureFlags = Cache::get('feature_flags');

            if (!$featureFlags) {
                // Use a cache lock to prevent multiple processes from calling the URL at once
                $lock = Cache::lock('feature_flags_lock', 10); // Lock held for 10 seconds

                try {
                    // Attempt to acquire the lock, wait max 3 seconds
                    $featureFlags = $lock->block(3, function () use ($url) {
                        logger()->info('Acquired lock for feature flag fetch');

                        try {
                            $res = Http::timeout(10)
                                ->withOptions(['read_timeout' => 30])
                                ->retry(3, 2000, function ($exception, $requestNumber) use ($url) {
                                    logger()->warning('Retrying feature flag fetch', [
                                        'url' => $url,
                                        'attempt' => $requestNumber,
                                        'error' => $exception->getMessage(),
                                    ]);
                                })
                                ->get($url);
                        } catch (ConnectionException $e) {
                            logger()->error('ConnectionException when fetching feature flags', [
                                'url' => $url,
                                'error' => $e->getMessage(),
                            ]);

                            return [];
                        }

                        if (!$res->successful()) {
                            logger()->error('Failed to fetch feature flags', [
                                'url' => $url,
                                'status' => $res->status(),
                                'body' => $res->body(),
                            ]);
                            return [];
                        }

                        $flags = $res->json();

                        // Cache flags for 60 minutes
                        Cache::put('feature_flags', $flags, now()->addMinutes(60));

                        return $flags;
                    });
                } catch (LockTimeoutException $e) {
                    logger()->warning('Could not acquire lock to fetch feature flags; using stale or default', [
                        'error' => $e->getMessage(),
                    ]);

                    $featureFlags = [];
                }
            }

            if (is_array($featureFlags) && !empty($featureFlags)) {
                app(FeatureFlagManager::class)->define($featureFlags);
            } else {
                logger()->warning('No feature flags were defined - empty or failed response.', ['url' => $url]);
            }
        });
    }
}
