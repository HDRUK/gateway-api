<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

class FeatureFlagManager
{
    public function define(array $flags, string $prefix = ''): void
    {
        foreach ($flags as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (isset($value['enabled']) && is_bool($value['enabled'])) {
                    Feature::define($fullKey, $value['enabled']);
                    Log::info("Feature flag defined: {$fullKey} = " . ($value['enabled'] ? 'ENABLED' : 'DISABLED'));
                }

                foreach ($value as $subKey => $subVal) {
                    if (is_array($subVal)) {
                        $this->define([$subKey => $subVal], $fullKey);
                    }
                }
            }
        }
    }
}
