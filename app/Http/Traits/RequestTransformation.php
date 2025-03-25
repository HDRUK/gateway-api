<?php

namespace App\Http\Traits;

trait RequestTransformation
{
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
}
