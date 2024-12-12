<?php

namespace App\Http\Traits;

trait TrimPayload
{
    public function trimDatasets(array $input, array $requiredFields): array
    {
        $miniMetadata = $input['metadata'];

        foreach ($miniMetadata as $key => $value) {
            if (!in_array($key, $requiredFields)) {
                unset($miniMetadata[$key]);
            }
        }

        return $miniMetadata;
    }
}
