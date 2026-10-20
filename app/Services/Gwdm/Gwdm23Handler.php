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

    public function isDatasetRecord(array $envelope): bool
    {
        return data_get($envelope, 'metadata.required.recordType', self::RECORD_TYPE_DATASET)
            !== self::RECORD_TYPE_CASE_STUDY;
    }
}
