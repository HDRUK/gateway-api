<?php

namespace App\Services;

use Laravel\Pennant\Feature;
use App\Models\FeatureFlag;

class FeatureFlagManager
{
    public function define(array $flags, string $prefix = ''): void
    {
        foreach ($flags as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (array_key_exists('enabled', $value) && is_bool($value['enabled'])) {
                    Feature::define($fullKey, $value['enabled']);
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
        return FeatureFlag::all()->reduce(function ($carry, $flag) {
            $carry[$flag->key] = ['enabled' => $flag->enabled];
            return $carry;
        }, []);
    }

}
