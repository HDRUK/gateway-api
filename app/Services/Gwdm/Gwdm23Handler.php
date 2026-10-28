<?php

namespace App\Services\Gwdm;

/**
 * GWDM 2.3 adds population and waveOf (no behaviour change), and
 * required.recordType, so a GWDM record is no longer always a dataset:
 * "caseStudy" records (CROCOD trial case studies) are stored the same way but
 * must stay out of dataset search.
 */
class Gwdm23Handler extends Gwdm2xHandler
{
    public const RECORD_TYPE_DATASET = 'dataset';
    public const RECORD_TYPE_CASE_STUDY = 'caseStudy';

    public function hasRecordTypes(): bool
    {
        return true;
    }

    /**
     * Adds the GWDM 2.3 population section's categorical fields, which
     * advanced search filters on.
     */
    public function toSearchableFields(array $envelope): array
    {
        $population = data_get($envelope, 'metadata.population', []) ?? [];

        return array_merge(parent::toSearchableFields($envelope), [
            'waveType' => $this->nonEmptyString(data_get($population, 'wave.waveType')),
            'recruitmentStatus' => $this->nonEmptyString(data_get($population, 'recruitment.status')),
            'followUpFrequency' => $this->nonEmptyString(data_get($population, 'followUpSchedule.frequency')),
            'isLongitudinal' => $this->booleanOrNull(data_get($population, 'studyDesign.isLongitudinal')),
            'isFamilyBased' => $this->booleanOrNull(data_get($population, 'studyDesign.isFamilyBased')),
        ]);
    }

    public function isDatasetRecord(array $envelope): bool
    {
        return data_get($envelope, 'metadata.required.recordType', self::RECORD_TYPE_DATASET)
            !== self::RECORD_TYPE_CASE_STUDY;
    }

    // Unknown stays unknown: only a stated true or false is indexed.
    private function booleanOrNull(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}
