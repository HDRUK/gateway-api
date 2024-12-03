<?php


if (! function_exists('convertArrayToStringWithKeyName')) {
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

if (! function_exists('convertArrayToArrayWithKeyName')) {
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
