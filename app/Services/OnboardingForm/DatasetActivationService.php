<?php

namespace App\Services\OnboardingForm;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\DatasetService;
use App\Services\Gwdm\SourceSchema;
use Config;
use Exception;
use MetadataManagementController as MMC;

class DatasetActivationService
{
    public function __construct(
        private readonly DatasetService $datasetService,
        private readonly OnboardingFormService $onboardingFormService,
    ) {
    }

    /**
     * Whether the latest version can be made active. Drafts are translated
     * without validation, so they are validated here as saving them active
     * would: the submitted metadata against its source schema, then the GWDM.
     * GWDM alone misses source errors the translation fills with placeholders.
     * The source check is skipped when the version stores no original (as
     * delta rows do) or its source schema is GWDM or not fully known.
     * A required list of values left empty in the schema the metadata was
     * submitted in fails too, as the onboarding form refuses it.
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

        $source = SourceSchema::fromVersion($latest);
        $original = DatasetVersion::submittedMetadata($envelope);
        $checksSource = $source->model !== null
            && $source->version !== null
            && $source->model !== Config::get('metadata.GWDM.name')
            && !empty($original);

        if ($checksSource && !$this->validates($original, $source->model, $source->version)) {
            return false;
        }

        if ($checksSource && $this->hasEmptyValueList($original, $source->model, $source->version)) {
            return false;
        }

        return $this->validates(
            $envelope['metadata'],
            Config::get('metadata.GWDM.name'),
            $envelope['gwdmVersion'],
        );
    }

    private function hasEmptyValueList(array $metadata, string $schema, string $version): bool
    {
        try {
            $jsonSchema = $this->onboardingFormService->schemaFor($schema, $version);
        } catch (Exception $e) {
            \Log::warning('Onboarding activation schema fetch failed', [
                'model' => $schema,
                'version' => $version,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return JsonSchema::hasEmptyRequiredValueList($jsonSchema, $jsonSchema, $metadata);
    }

    private function validates(array $metadata, string $schema, string $version): bool
    {
        $metadataJson = json_encode(['metadata' => $metadata]);

        return MMC::validateDataModelType($metadataJson, $schema, $version);
    }
}
