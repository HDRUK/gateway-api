<?php

namespace App\FeatureFlags;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FeatureFlagManager
{
    protected Filesystem $storage;
    protected array $features = [];

    public function __construct()
    {
        //$this->storage = app('filesystem')->disk('gcs.feature_flags');
        $this->loadFeatures();
    }

    protected function loadFeatures(): void
    {
        Log::info('Loading feature flags...');

        $this->features = cache()->remember('feature_flags.json', now()->addMinutes(30), function () {
            if (app()->environment('local')) {
                Log::info('Local environment detected. Using HTTP Call, no cache.');
                return [
                    'SDEConciergeServiceEnquiry' => ['enabled' => true],
                    'Aliases' => ['enabled' => true],
                ];
                // $url = env('FEATURE_FLAGGING_CONFIG_URL');
                // $res = Http::get($url);
                // if ($res->successful()) {
                //     return $res->json();
                // }

                // logger()->error('Failed to fetch feature flags from URL', ['url' => $url]);
                // return [];
            }

            try {
                try {
                    $json = $this->storage->get('features.json');
                    Log::info('Successfully fetched features.json from GCS.');
                } catch (\Throwable $e) {
                    Log::error('Error accessing GCS bucket for features.json: ' . $e->getMessage());
                    return [];
                }

                $features = json_decode($json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Failed to decode features.json: ' . json_last_error_msg());
                    return [];
                }

                Log::info('Feature flags loaded and parsed successfully.', ['features' => $features]);

                return $features;

            } catch (\Throwable $e) {
                Log::error('Unexpected error loading features.json: ' . $e->getMessage());
                return [];
            }
        });
    }



    public function reload(): void
    {
        Log::info('Reloading feature flags: Clearing cache and reloading...');
        cache()->forget('feature_flags.json');
        $this->loadFeatures();
    }

    public function getEnabledFeatures(): array
    {
        $this->loadFeatures();

        $enabledFeatures = array_filter($this->features, function ($feature) {
            return is_array($feature) && ($feature['enabled'] ?? false) === true;
        });

        Log::info('Fetching all enabled feature flags.', ['enabled_features' => array_keys($enabledFeatures)]);

        return $enabledFeatures;
    }

    public function resolve(string $feature, mixed $scope = null): mixed
    {
        $this->loadFeatures();

        $value = $this->features[$feature] ?? false;
        Log::info('Resolving feature flag.', [
            'feature' => $feature,
            'resolved_value' => $value,
            'scope' => $scope,
        ]);

        return $value;
    }
}
