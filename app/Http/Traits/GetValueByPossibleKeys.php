<?php

namespace App\Http\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

trait GetValueByPossibleKeys
{
    /**
     * Search for a value in an array by trying multiple possible keys or paths in order.
     * 
     * Note 15th Oct 2024 - Tom Giles - I have now enhanced this function for full searching:
     * The function now supports both simple keys and dot notation paths. for example ['tools'] 
     * or ['linkages.tools'] or even ['metadata.linkages.tools'] will now return the ['tools'] 
     * object. Previously the full path in the array was requried. I have done this because 
     * widening the potential search parathenisis provides more flexible in terms maintaining 
     * functionality across data model changes. 
     *
     * @param array $array The array to search.
     * @param array $keys The list of possible keys or paths to try, in order.
     * @param mixed $default The default value to return if none of the keys are found.
     * @return mixed The value of the first key found, or the default value if none are found.
     */
    public function getValueByPossibleKeys(array $array, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (strpos($key, '.') !== false) {
                // Treat key as a dot notation path
                $value = $this->findByPath($array, $key);
                if (!is_null($value)) {
                    return $value;
                }
            } else {
                // Treat key as a simple key and search recursively
                $value = $this->recursiveSearch($array, $key);
                if (!is_null($value)) {
                    return $value;
                }
            }
        }

        // Optionally log if no keys are found
        // Log::info('No value found for any of the specified keys', [
        //     'keys' => $keys,
        // ]);

        return $default;
    }

    /**
     * Recursively search for a key in a multi-dimensional array.
     *
     * @param array $array The array to search.
     * @param string $key The key to search for.
     * @return mixed|null The value if found, or null otherwise.
     */
    protected function recursiveSearch(array $array, string $key)
    {
        foreach ($array as $k => $v) {
            if ($k === $key) {
                return $v;
            }

            if (is_array($v)) {
                $result = $this->recursiveSearch($v, $key);
                if (!is_null($result)) {
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Find a value in a multi-dimensional array based on a dot notation path.
     * This function searches through all possible branches to find the path.
     *
     * @param array $array The array to search.
     * @param string $path The dot notation path to search for.
     * @return mixed|null The value if found, or null otherwise.
     */
    protected function findByPath(array $array, string $path)
    {
        $segments = explode('.', $path);

        return $this->recursivePathSearch($array, $segments);
    }

    /**
     * Helper function to perform recursive path search.
     *
     * @param array $array The current array segment.
     * @param array $segments The remaining path segments to search.
     * @return mixed|null The value if found, or null otherwise.
     */
    protected function recursivePathSearch(array $array, array $segments)
    {
        $currentSegment = array_shift($segments);

        if (array_key_exists($currentSegment, $array)) {
            $value = $array[$currentSegment];
            if (empty($segments)) {
                return $value;
            }

            if (is_array($value)) {
                return $this->recursivePathSearch($value, $segments);
            }
        }

        // If the current segment is not found, search deeper in the array
        foreach ($array as $key => $subArray) {
            if (is_array($subArray)) {
                $result = $this->recursivePathSearch($subArray, array_merge([$currentSegment], $segments));
                if (!is_null($result)) {
                    return $result;
                }
            }
        }

        return null;
    }
}