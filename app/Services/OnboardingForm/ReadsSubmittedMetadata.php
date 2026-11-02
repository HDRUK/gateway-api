<?php

namespace App\Services\OnboardingForm;

use App\Models\DatasetVersion;
use App\Services\Gwdm\SourceSchema;

trait ReadsSubmittedMetadata
{
    private function sourceSchema(DatasetVersion $version): SourceSchema
    {
        $source = SourceSchema::fromColumns($version->only(['source_schema_model', 'source_schema_version']));

        return $source->model !== null ? $source : SourceSchema::declared(null, null, (array) $version->metadata);
    }

    /**
     * The document as submitted in its source schema. Some writes store the
     * whole form envelope ({schemaModel, schemaVersion, metadata}) as the
     * original, so it is unwrapped.
     */
    private function submittedMetadata(array $envelope): array
    {
        $original = (array) ($envelope['original_metadata'] ?? []);
        if (isset($original['schemaModel'], $original['metadata'])) {
            $original = (array) $original['metadata'];
        }

        return $original;
    }
}
