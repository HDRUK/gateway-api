<?php

namespace App\Models\Traits;

use DB;

trait ModelHelpers
{
    public function countDursForDatasetVersion(int $datasetVersionId): null|int
    {
        $result = DB::select(
            '
                SELECT
                    COUNT(id) AS count
                FROM dur_has_dataset_version
                WHERE
                    dataset_version_id = :dataset_version_id
                AND
                    deleted_at is NULL
            ',
            [
                'dataset_version_id' => $datasetVersionId,
            ]
        );

        if (count($result) > 0) {
            return $result[0]->count;
        }

        return null;
    }

    public function countPublicationsForDatasetVersion(int $datasetVersionId): null|int
    {
        $result = DB::select(
            '
                SELECT
                    COUNT(id) AS count
                FROM publication_has_dataset_version
                WHERE
                    dataset_version_id = :dataset_version_id
                AND
                    deleted_at IS NULL
            ',
            [
                'dataset_version_id' => $datasetVersionId,
            ]
        );

        if (count($result) > 0) {
            return $result[0]->count;
        }

        return null;
    }
}
