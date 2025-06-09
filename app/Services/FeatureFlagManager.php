<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Feature;
// use Illuminate\Support\Facades\Cache;
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


                    Log::info("Feature flag defined: {$fullKey} = " . ($value['enabled'] ? 'ENABLED' : 'DISABLED'));
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
        $featureFlags = Cache::remember('getAllFlags', now()->addMinutes(60), function () use ($url) {
            $res = Http::get($url);
            if ($res->successful()) {
                return $res->json();
            }

            logger()->error('Failed to fetch feature flags from URL', ['url' => $url]);
            return [];
        });




        return $featureFlags;
    }
}
