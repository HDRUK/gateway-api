<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

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
                $this->defineFeatureFlags($featureFlags);
            }
        } else {
            logger()->error('Failed to fetch feature flags from URL', ['url' => $url]);
        }

        if (is_array($featureFlags)) {
            $this->defineFeatureFlags($featureFlags);
        }
    }

    protected function defineFeatureFlags(array $flags, string $prefix = '')
    {
        foreach ($flags as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                if (isset($value['enabled']) && is_bool($value['enabled'])) {
                    Feature::define($fullKey, $value['enabled']);
                    logger()->info("Feature flag defined: {$fullKey} = " . ($value['enabled'] ? 'ENABLED' : 'DISABLED'));
                }

                foreach ($value as $subKey => $subVal) {
                    if (is_array($subVal)) {
                        $this->defineFeatureFlags([$subKey => $subVal], $fullKey);
                    }
                }
            }
        }
    }
}
