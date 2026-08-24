<?php

namespace App\Http\Traits;

use Illuminate\Support\Arr;

trait MetadataExtraction
{
    /**
     * Extracts metadata from the given mixed input.
     *
     * @return array
     */
    private function extractMetadata(mixed $metadata)
    {
        if (is_array($metadata) && Arr::has($metadata, 'metadata.metadata')) {
            $metadata = $metadata['metadata'];
        } elseif (is_array($metadata) && ! Arr::has($metadata, 'metadata')) {
            $metadata = [
                'metadata' => $metadata,
            ];
        }

        if (is_string($metadata) && isJsonString($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        // Pre-process check for incoming data from a resource that passes strings
        // when we expect an associative array. FMA passes strings, this
        // is a safe-guard to ensure execution is unaffected by other data types.

        if (isset($metadata['metadata']) && is_string($metadata['metadata']) && isJsonString($metadata['metadata'])) {
            $tmpMetadata['metadata'] = json_decode($metadata['metadata'], true);
            unset($metadata['metadata']);
            $metadata = $tmpMetadata;
        }

        return $metadata;
    }
}
