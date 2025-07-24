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

    public function countActiveDursForDatasetVersion(int $datasetVersionId): null|int
    {
        $result = DB::select(
            '
                SELECT
                    COUNT(dur_has_dataset_version.id) AS count
                FROM dur_has_dataset_version
                INNER JOIN dur on dur.id = dur_has_dataset_version.dur_id
                INNER JOIN dataset_versions on dataset_versions.id = dur_has_dataset_version.dataset_version_id
                INNER JOIN datasets on datasets.id = dataset_versions.dataset_id
                WHERE
                    dur_has_dataset_version.dataset_version_id = :dataset_version_id
                AND
                    dur_has_dataset_version.deleted_at is NULL
                AND
                    dur.status = \'ACTIVE\'
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

    public function countActivePublicationsForDatasetVersion(int $datasetVersionId): null|int
    {
        $result = DB::select(
            '
                SELECT
                    COUNT(publication_has_dataset_version.id) AS count
                FROM publication_has_dataset_version
                INNER JOIN publications on publications.id = publication_has_dataset_version.publication_id
                INNER JOIN dataset_versions on dataset_versions.id = publication_has_dataset_version.dataset_version_id
                INNER JOIN datasets on datasets.id = dataset_versions.dataset_id
                WHERE
                    publication_has_dataset_version.dataset_version_id = :dataset_version_id
                AND
                    publication_has_dataset_version.deleted_at is NULL
                AND
                    publications.status = \'ACTIVE\'
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
