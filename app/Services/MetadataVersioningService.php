<?php

namespace App\Services;

use App\Models\MetadataVersion;
use Illuminate\Support\Facades\DB;
use Swaggest\JsonDiff\JsonDiff;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonPointer\JsonPointer;

class MetadataVersioningService
{
    public function getLatestVersion(int $datasetId): int
    {
        return MetadataVersion::where('dataset_id', $datasetId)->max('version') ?? 0;
    }

    public function getCurrentState(int $datasetId): array
    {
        $base = MetadataVersion::where('dataset_id', $datasetId)
            ->where('version', 1)
            ->first();

        if (!$base || empty($base->snapshot)) {
            return [];  // No base version found, return empty state
        }

        $state = json_decode($base->snapshot, true);

        $versions = MetadataVersion::where('dataset_id', $datasetId)
            ->where('version', '>', 1)
            ->orderBy('version')
            ->get();

        foreach ($versions as $version) {
            $patch = JsonPatch::import($version->patch);
            $newState = json_decode(json_encode($state));
            $state = $patch->apply($newState);

            $state = (array)$newState;
        }

        return $state;
    }

    public function updateMetadata(int $datasetId, array $newPayload): void
    {
        DB::transaction(function () use ($datasetId, $newPayload) {
            $currentState = $this->getCurrentState($datasetId);

            $old = json_decode(json_encode($currentState), true);
            $new = json_decode(json_encode($newPayload), true);

            $nextVersion = $this->getLatestVersion($datasetId) + 1;
            // Very first version should store the full snapshot
            if ($nextVersion === 1) {
                MetadataVersion::create([
                    'dataset_id' => $datasetId,
                    'version' => $nextVersion,
                    'patch' => [],
                    'snapshot' => json_encode($newPayload),
                ]);
                return;
            }

            $diff = new JsonDiff($old, $new);
            $patch = $diff->getPatch();
            $patchOps = $patch->jsonSerialize();

            if (empty($patchOps)) {
                // NO changes, no version needed
                return;
            }

            // Only store delta diffs from now on
            MetadataVersion::create([
                'dataset_id' => $datasetId,
                'version' => $nextVersion,
                'patch' => $patchOps,
                'snapshopt' => null,
            ]);
        });
    }

    public function getDatasetAtVersion(int $datasetId, int $version): array
    {
        $base = MetadataVersion::where('dataset_id', $datasetId)
            ->where('version', 1)
            ->first();

        if (!$base || empty($base->snapshot)) {
            throw new \Exception('Base version not found for dataset ID ' . $datasetId);
        }

        // Snapshot starts as associative array
        $state = json_decode($base->snapshot, true);

        $versions = MetadataVersion::where('dataset_id', $datasetId)
            ->where('version', '>', 1)
            ->where('version', '<=', $version)
            ->orderBy('version')
            ->get();

        foreach ($versions as $version) {
            $patch = JsonPatch::import($version->patch);
            $newState = json_decode(json_encode($state));
            $patch->apply($newState);

            $state = json_decode(json_encode($newState), true);
        }

        return $state;
    }
}