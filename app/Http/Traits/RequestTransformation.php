<?php

namespace App\Http\Traits;

trait RequestTransformation
{
    public function checkEditArray(array $input, array $keys): array
    {
        $response = [];

        foreach($input as $key => $value) {
            if (array_key_exists($key, $keys)) {
                $response[$key] = $value;
            }
        }

        return $response;
    }
}