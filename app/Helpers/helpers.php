<?php


if (!function_exists('convertArrayToStringWithKeyName')) {
    function convertArrayToStringWithKeyName($array, $keyname, $separator = ',')
    {
        $temp = [];
        foreach ($array as $item) {
            $temp[] = $item[$keyname];
        }
        $temp = array_unique($temp);

        return implode($separator, $temp);
    }
}
if (!function_exists('isJsonString')) {
    function isJsonString($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('formatCleanInput')) {
    function formatCleanInput($input)
    {
        $decoded_input = html_entity_decode($input, ENT_QUOTES, 'UTF-8');
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $sanitized_input = $purifier->purify($decoded_input);
        return str_replace('&amp;', '&', $sanitized_input);
        ;
    }
}

if (!function_exists('convertArrayToArrayWithKeyName')) {
    function convertArrayToArrayWithKeyName($array, $keyname)
    {
        $return = [];
        foreach ($array as $item) {
            $return[] = $item[$keyname];
        }
        $return = array_unique($return);

        return $return;
    }
}

if (!function_exists('convertArrayToHtmlUlList')) {
    /**
     * convertArrayToHtmlUlList function
     *
     * @param array $array example: ["a", "b", "c", ...]
     * @return string
     */
    function convertArrayToHtmlUlList(array $array): string
    {
        if (!count($array)) {
            return '';
        }

        $return = '<ul>';
        foreach ($array as $item) {
            $return .= '<li>' . $item . '</li>';
        }
        $return .= '</ul>';
        return $return;
    }
}

if (!function_exists('extractValueFromPath')) {
    /**
     * Extract a value from a nested array using a slash-delimited path.
     *
     * @param  array   $item  The array to extract the value from.
     * @param  string  $path  The slash-delimited path to the value (e.g. 'foo/bar/baz').
     * @return mixed          The value at the given path, or null if not found.
     *
     * @example
     * extractValueFromPath(['foo' => ['bar' => 'baz']], 'foo/bar'); // 'baz'
     */
    function extractValueFromPath(array $item, string $path)
    {
        $keys = explode('/', $path);

        $return = $item;
        foreach ($keys as $key) {
            if (isset($return[$key])) {
                $return = $return[$key];
            } else {
                return null;
            }
        }

        return $return;
    }
}

if (!function_exists('arrayColumnToString')) {
    /**
     * Extract a column from a multi-dimensional array and join the values as a comma-separated string.
     *
     * Null and empty values are filtered out before joining.
     *
     * @param  array|null   $array  The input array to extract values from. Returns null if array is null.
     * @param  string|null  $key    The key to extract from each element. Returns null if key is null.
     * @return string|null          A comma-separated string of values, or null if the array/key is null or array is empty.
     *
     * @example
     * arrayColumnToString([['id' => 1], ['id' => 2], ['id' => 3]], 'id');          // '1,2,3'
     * arrayColumnToString(['{"id":1}', '{"id":2}', '{"id":3}'], 'id');             // '1,2,3'
     * arrayColumnToString(null, 'id');                                              // null
     * arrayColumnToString([['id' => 1], ['id' => 2]], null);
     */
    function arrayColumnToString(?array $array, ?string $key): ?string
    {
        if (is_null($array) || is_null($key)) {
            return null;
        }

        if (count($array)) {
            $decoded = array_map(function ($item) {
                return is_string($item) ? json_decode($item, true) : $item;
            }, $array);

            $return = array_column($decoded, $key);
            return implode(',', array_filter($return));
        }

        return null;
    }
}

if (! function_exists('decodeHtmlEntitiesRecursive')) {
    /**
     * Recursively decode HTML entities in a string, array, or nested structure.
     *
     * Common conversions:
     *   &gt;   → >
     *   &lt;   → <
     *   &amp;  → &
     *   &quot; → "
     *   &#039; → '
     *
     * @param  mixed  $data     The value to decode. Accepts a string, array
     *                          (including nested/multidimensional), or any
     *                          scalar. Objects are returned as-is.
     * @param  int    $flags    Bitmask of ENT_* constants passed to
     *                          html_entity_decode(). Defaults to
     *                          ENT_QUOTES | ENT_HTML5, which decodes both
     *                          single and double quoted entities using the
     *                          full HTML5 entity table.
     * @param  string $encoding Character encoding to use for decoding.
     *                          Defaults to 'UTF-8'.
     *
     * @return mixed            The decoded value, preserving the original type
     *                          and structure of the input.
     *
     * @example
     *   // Plain string
     *   decode_html_entities_recursive('Hello &gt; World');
     *   // → 'Hello > World'
     *
     * @example
     *   // Flat array
     *   decode_html_entities_recursive(['a' => '&lt;b&gt;', 'c' => 42]);
     *   // → ['a' => '<b>', 'c' => 42]
     *
     * @example
     *   // Multidimensional / JSON payload
     *   $payload = json_decode($request->getContent(), true);
     *   $clean   = decode_html_entities_recursive($payload);
     */
    function decodeHtmlEntitiesRecursive(
        mixed $data,
        int $flags = ENT_QUOTES | ENT_HTML5,
        string $encoding = 'UTF-8',
    ): mixed {
        if (is_string($data)) {
            return html_entity_decode($data, $flags, $encoding);
        }

        if (is_array($data)) {
            return array_map(
                static fn (mixed $item): mixed => decodeHtmlEntitiesRecursive($item, $flags, $encoding),
                $data,
            );
        }

        // return untouched
        return $data;
    }
}
