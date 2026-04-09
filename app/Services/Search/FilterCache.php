<?php

namespace App\Services\Search;

use App\Models\Filter;
use Illuminate\Support\Facades\Cache;

class FilterCache
{
    private const TTL = 300;

    public static function get(string $type, bool $enabledOnly = false): array
    {
        $key = 'search_filters.' . $type . ($enabledOnly ? '.enabled' : '');

        return Cache::remember($key, self::TTL, function () use ($type, $enabledOnly) {
            $query = Filter::where('type', $type);
            if ($enabledOnly) {
                $query->where('enabled', 1);
            }
            return $query->get()->toArray();
        });
    }
}
