<?php

namespace App\Http\Middleware;

use App\Services\FeatureFlagManager;
use Closure;

class DefineFeatureFlags
{
    public function handle($request, Closure $next)
    {
        $url = config('gateway.feature_flagging_config_url');
        if (app()->environment('testing') || !$url) {
            return $next($request);
        }

        $flagManager = app(FeatureFlagManager::class);



        $featureFlags = $flagManager->getAllFlags();

        if (is_array($featureFlags) && !empty($featureFlags)) {
            $flagManager->define($featureFlags);
        } else {
            logger()->warning('No feature flags were defined - empty or failed response.', ['url' => $url]);
        }

        return $next($request);
    }
}
