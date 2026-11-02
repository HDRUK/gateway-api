<?php

namespace App\Services\OnboardingForm;

use App\Models\Dataset;
use App\Services\DatasetService;
use Config;
use MetadataManagementController as MMC;

class DatasetActivationService
{
    use ReadsSubmittedMetadata;

    public function __construct(
        private readonly DatasetService $datasetService,
    ) {
    }

    /**
     * Whether the latest version can be made active. Drafts are translated
     * without validation, so they are validated here as saving them active
     * would: the submitted metadata against its source schema, then the GWDM.
     * GWDM alone misses source errors the translation fills with placeholders.
     * The source check is skipped when the version stores no original (as
     * delta rows do) or its source schema is GWDM or not fully known.
     */
    public function isValid(Dataset $dataset): bool
    {
        $latest = $dataset->latestVersion();
        if (!$latest) {
            return false;
        }

        $envelope = $this->datasetService->getReconstructedMetadataEnvelope(
            $dataset->id,
            $latest->version,
            validate: false,
            prefetched: $latest,
        );

        $source = $this->sourceSchema($latest);
        $original = $this->submittedMetadata($envelope);
        if (
            $source->model !== null
            && $source->version !== null
            && $source->model !== Config::get('metadata.GWDM.name')
            && !empty($original)
            && !$this->validates($original, $source->model, $source->version)
        ) {
            return false;
        }

        return $this->validates(
            $envelope['metadata'],
            Config::get('metadata.GWDM.name'),
            $envelope['gwdmVersion'],
        );
    }

    private function validates(array $metadata, string $schema, string $version): bool
    {
        $metadataJson = json_encode(['metadata' => $metadata]);

        return MMC::validateDataModelType($metadataJson, $schema, $version);
    }
}
