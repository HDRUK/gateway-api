<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Tests\TestCase;

/**
 * Dataset::latestVersionID() runs a raw DB::select() query, which bypasses
 * Eloquent's automatic soft-delete global scope. If the highest-versioned
 * row for a dataset is soft-deleted, this must still skip it and return the
 * latest non-deleted version instead — matching the behaviour of the
 * Eloquent-based latestVersion() right above it in the same class, and
 * DatasetVersion::shouldBeSearchable(), which already filters
 * whereNull('deleted_at') when computing "the latest version" elsewhere.
 */
class DatasetLatestVersionIdSoftDeleteTest extends TestCase
{
    private function seededDatasetId(): int
    {
        return Dataset::query()->firstOrFail()->id;
    }

    public function test_latest_version_id_skips_soft_deleted_latest_version(): void
    {
        $datasetId = $this->seededDatasetId();

        [$v1, $v2] = DatasetVersion::withoutEvents(function () use ($datasetId) {
            $v1 = DatasetVersion::create([
                'dataset_id' => $datasetId,
                'version' => 201,
                'metadata' => json_encode(['gwdmVersion' => '2.0']),
            ]);

            $v2 = DatasetVersion::create([
                'dataset_id' => $datasetId,
                'version' => 202,
                'metadata' => json_encode(['gwdmVersion' => '2.0']),
            ]);

            return [$v1, $v2];
        });

        // Soft-delete the highest-versioned row.
        $v2->delete();

        $dataset = Dataset::find($datasetId);

        $this->assertSame(
            $v1->id,
            $dataset->latestVersionID($datasetId),
            'latestVersionID() must skip a soft-deleted version and return the latest non-deleted one'
        );
    }

    public function test_latest_version_id_with_gwdm_version_skips_soft_deleted_latest_version(): void
    {
        $datasetId = $this->seededDatasetId();

        [$v1, $v2] = DatasetVersion::withoutEvents(function () use ($datasetId) {
            $v1 = DatasetVersion::create([
                'dataset_id' => $datasetId,
                'version' => 301,
                'gwdm_version' => '2.1',
                'metadata' => json_encode(['gwdmVersion' => '2.1']),
            ]);

            $v2 = DatasetVersion::create([
                'dataset_id' => $datasetId,
                'version' => 302,
                'gwdm_version' => '2.1',
                'metadata' => json_encode(['gwdmVersion' => '2.1']),
            ]);

            return [$v1, $v2];
        });

        $v2->delete();

        $dataset = Dataset::find($datasetId);

        $this->assertSame(
            $v1->id,
            $dataset->latestVersionID($datasetId, '2.1'),
            'latestVersionID() with a gwdm_version filter must also skip soft-deleted rows'
        );
    }
}
