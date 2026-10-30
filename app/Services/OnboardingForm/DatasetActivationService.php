<?php

namespace App\Services\OnboardingForm;

use App\Models\Dataset;
use App\Services\DatasetService;
use Config;
use MetadataManagementController as MMC;

class DatasetActivationService
{
    public function __construct(
        private readonly DatasetService $datasetService,
    ) {
    }

    /**
     * Why the latest version's stored GWDM cannot be made active. Drafts are
     * translated without validation, so they are validated here instead.
     *
     * @return array<int, array<string, mixed>>
     */
    public function errors(Dataset $dataset): array
    {
        $latest = $dataset->latestVersion();
        if (!$latest) {
            return [['message' => 'Dataset has no metadata']];
        }

        $envelope = $this->datasetService->getReconstructedMetadataEnvelope(
            $dataset->id,
            $latest->version,
            validate: false,
            prefetched: $latest,
        );

        $metadataJson = json_encode(['metadata' => $envelope['metadata']]);
        $valid = MMC::validateDataModelType(
            $metadataJson,
            Config::get('metadata.GWDM.name'),
            $envelope['gwdmVersion'],
        );

        return $valid ? [] : (MMC::validationErrors(
            json_encode($envelope['metadata']),
            Config::get('metadata.GWDM.name'),
            $envelope['gwdmVersion'],
        ) ?: [['message' => 'GWDM failed validation']]);
    }
}
