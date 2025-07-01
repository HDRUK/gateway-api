<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FeatureFlagManager;

class FeatureServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->booted(function () {
            logger()->info('Starting features');
            $url = env('FEATURE_FLAGGING_CONFIG_URL');

            $flagManager = app(FeatureFlagManager::class);

            if (app()->environment('testing') || !$url) {
                return;
            }

            $featureFlags = $flagManager->getAllFlags();

            if (is_array($featureFlags) && !empty($featureFlags)) {
                $flagManager->define($featureFlags);
            } else {
                logger()->warning('No feature flags were defined - empty or failed response.', ['url' => $url]);
            }

        });
    }


}
