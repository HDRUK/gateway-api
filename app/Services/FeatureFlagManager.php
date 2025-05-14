<?php

namespace App\Services;

use Laravel\Pennant\Feature;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlagManager
{
    public const CACHE_KEY = 'defined_feature_flags';

    public function define(array $flags, string $prefix = ''): void
    {
        foreach ($flags as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (array_key_exists('enabled', $value) && is_bool($value['enabled'])) {
                    Feature::define($fullKey, $value['enabled']);


                    $defined = Cache::get(self::CACHE_KEY, []);
                    $defined[] = $fullKey;
                    Cache::put(self::CACHE_KEY, array_unique($defined));

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
        return Cache::get(self::CACHE_KEY, []);
    }
}
