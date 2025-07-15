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
