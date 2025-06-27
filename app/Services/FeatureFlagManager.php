<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Feature;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlagManager
{
    public function define(array $flags, string $prefix = ''): void
    {
        foreach ($flags as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (array_key_exists('enabled', $value) && is_bool($value['enabled'])) {
                    Feature::define($fullKey, $value['enabled']);


                    // Log::info("Feature flag defined: {$fullKey} = " . ($value['enabled'] ? 'ENABLED' : 'DISABLED'));
                }


                if (isset($value['features']) && is_array($value['features'])) {
                    $this->define($value['features'], $fullKey);
                }


                foreach ($value as $subKey => $subVal) {
                    if (is_array($subVal) && $subKey !== 'features' && $subKey !== 'enabled') {
                        $this->define([$subKey => $subVal], $fullKey);
                    }
                }
            }
        }
    }


    public function getAllFlags(): array
    {
        $url = env('FEATURE_FLAGGING_CONFIG_URL');

        $featureFlags = Cache::get('getAllFlags');

        if (!$featureFlags) {
            $lock = Cache::lock('getAllFlags_lock', 10);

            try {
                $featureFlags = $lock->block(3, function () use ($url) {
                    logger()->info('Fetching all feature flags with lock');

                    try {
                        $res = Http::timeout(60)
                            ->retry(3, 2000, function ($exception, $requestNumber) use ($url) {
                                logger()->warning('Retrying feature flag fetch', [
                                    'url' => $url,
                                    'attempt' => $requestNumber,
                                    'error' => $exception->getMessage(),
                                ]);
                            })
                            ->get($url);
                    } catch (\Throwable $e) {
                        logger()->error('Exception fetching all flags', ['error' => $e->getMessage()]);
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

                    // Cache the result for 60 minutes
                    Cache::put('getAllFlags', $flags, now()->addMinutes(60));

                    return $flags;
                });
            } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                logger()->warning('Could not acquire lock for getAllFlags', ['error' => $e->getMessage()]);

                $featureFlags = Cache::get('getAllFlags', [
                        'SDEConciergeServiceEnquiry' => ['enabled' => env('SDEConciergeServiceEnquiry', true)],
                        'Aliases' => ['enabled' => true],
            ]);
            }
        }

        return $featureFlags;
    }

}
