<?php

namespace App\Http\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait GetValueByPossibleKeys
{
    /**
     * Search for a value in an array by trying multiple possible keys in order.
     *
     * @param array $array The array to search.
     * @param array $keys The list of possible keys to try, in order.
     * @param mixed $default The default value to return if none of the keys are found.
     * @return mixed The value of the first key found, or the default value if none are found.
     */
    public function getValueByPossibleKeys(array $array, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $value = Arr::get($array, $key, null);
            if (!is_null($value)) {
                return $value;
            }
        }
        Log::info('No value found for any of the specified keys', [
            'keys' => $keys,
        ]);
        return $default;
    }
}
