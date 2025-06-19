<?php

namespace App\Http\Traits;

trait RequestTransformation
{
    // Return the subarray of $input whose keys exist in the reference list $keys
    public function checkEditArray(array $input, array $keys): array
    {
        $response = [];

        foreach ($input as $key => $value) {
            if (in_array($key, $keys)) {
                $response[$key] = $value;
            }
        }

        return $response;
    }

    // Return an array of all $keys, with their values modified by values from $input if present
    public function checkUpdateArray(array $input, array $keys): array
    {
        $response = [];

        foreach ($keys as $key) {
            $response[$key] = null;
            if (in_array($key, $input)) {
                $response[$key] = $input[$key];
            }
        }

        return $response;
    }
}
