<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use App\Services\FeatureFlagManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\ConnectionException;

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

            // $featureFlags = Cache::remember('feature_flags', now()->addMinutes(10), function () use ($url) {
            //     logger()->info('Calling that Bucket');

            //     try {
            //         // this is the thing that fails, it does not retry and falls over because during a random split second http protocol does not exist...
            //         // this error we are getting is VERY similiar to the reddis connection problems.. they are likely the same underlying issue.
            //         $res = Http::timeout(60)
            //             ->retry(3, 2000, function ($exception, $requestNumber) use ($url) {
            //                 logger()->warning('Retrying feature flag fetch', [
            //                     'url' => $url,
            //                     'attempt' => $requestNumber,
            //                     'error' => $exception->getMessage(),
            //                 ]);
            //             })
            //             ->get($url);
            //     } catch (ConnectionException $e) {
            //         logger()->error('ConnectionException when fetching feature flags', [
            //             'url' => $url,
            //             'error' => $e->getMessage(),
            //         ]);
            //         /// this is temp until the new GCS solution JB is in place
            //         return [
            //         'SDEConciergeServiceEnquiry' => ['enabled' => env('SDEConciergeServiceEnquiry', true)],
            //         'Aliases' => ['enabled' => true],
            //     ];
            //     }

            //     if (!$res->successful()) {
            //         logger()->error('Failed to fetch feature flags', [
            //             'url' => $url,
            //             'status' => $res->status(),
            //             'body' => $res->body(),
            //         ]);
            //         return [];
            //     }

            //     return $res->json();
            // });

            $featureFlags = [
                    'SDEConciergeServiceEnquiry' => ['enabled' => env('SDEConciergeServiceEnquiry', true)],
                    'Aliases' => ['enabled' => true],
            ];
            app(FeatureFlagManager::class)->define($featureFlags);


            // if (is_array($featureFlags) && !empty($featureFlags)) {
            //     app(FeatureFlagManager::class)->define($featureFlags);
            // } else {
            //     logger()->warning('No feature flags were defined - empty or failed response.', ['url' => $url]);
            // }
        });
    }


}
