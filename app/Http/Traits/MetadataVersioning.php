<?php

namespace App\Http\Traits;

use Config;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;

trait MetadataVersioning
{
    use MetadataOnboard;

    /**
     * Create new dataset_version
     *
     * @param Dataset $currDataset the dataset metadata is being updated for
     * @param string $incomingStatus the status the dataset will be after this update
     * @param string $updateTime the time of the update
     * @param array $newMetadata the new metadata associated with the dataset
     * @param array $previousMetadata the previous version of metadata associated with the dataset
     */
    public function addMetadataVersion(
        Dataset $currDataset,
        string $incomingStatus,
        string $updateTime,
        array $newMetadata,
        array $previousMetadata,
        int $previousMetadataVersionNumber
    ): array {
        $versionNumber = 0;

        if($incomingStatus === Dataset::STATUS_ACTIVE) {
            // Determine the last version of metadata
            if ($currDataset->status !== Dataset::STATUS_DRAFT) {
                $versionNumber = $previousMetadataVersionNumber + 1;
            }
            $versionCode = $this->formatVersion($previousMetadataVersionNumber);
            $lastMetadata = $currDataset->lastMetadata();

            //update the GWDM modified date and version
            $gwdmMetadata['required']['modified'] = $updateTime;
            if(version_compare(Config::get('metadata.GWDM.version'), '1.0', '>')) {
                if(version_compare($lastMetadata['gwdmVersion'], '1.0', '>')) {
                    $newMetadata['required']['version'] = $versionCode;
                }
            }

            //update the GWDM revisions
            // NOTE: Calum 12/1/24
            //       - url set with a placeholder right now, should be revised before production
            //       - https://hdruk.atlassian.net/browse/GAT-3392
            $newMetadata['required']['revisions'][] = [
                "url" => env('GATEWAY_URL') . '/dataset' .'/' . $currDataset->id . '?version=' . $versionCode,
                "version" => $versionCode
            ];
        }

        $metadataSaveObject = [
            'gwdmVersion' =>  Config::get('metadata.GWDM.version'),
            'metadata' => $newMetadata,
            'original_metadata' => $previousMetadata,
        ];

        // Update or create new metadata version based on draft status
        if ($currDataset->status !== Dataset::STATUS_DRAFT) {
            // Now find previous attached relations and store those for the new version
            $dv = DatasetVersionHasDatasetVersion::where([
                'dataset_version_source_id' => $currDataset->latestVersionID($currDataset->id),
                'linkage_type' => DatasetVersionHasDatasetVersion::LINKAGE_TYPE_DATASETS,
            ])->get();

            $newVersion = DatasetVersion::create([
                'dataset_id' => $currDataset->id,
                'metadata' => json_encode($metadataSaveObject),
                'version' => $versionNumber,
            ]);

            unset($metadataSaveObject);

            // If relations were found, link those to the new metadata version
            if (count($dv) > 0) {
                foreach ($dv as $relation) {
                    DatasetVersionHasDatasetVersion::create([
                        'dataset_version_source_id' => $newVersion->id,
                        'dataset_version_target_id' => $relation->dataset_version_target_id,
                        'linkage_type' => $relation->linkage_type,
                        'direct_linkage' => $relation->direct_linkage,
                        'description' => $relation->description,
                    ]);
                }
            }
        } else {
            $versionNumber = $currDataset->lastMetadataVersionNumber()->version;

            // Update the existing version
            $newVersion = DatasetVersion::where([
                'dataset_id' => $currDataset->id,
                'version' => $versionNumber,
            ])->update([
                'metadata' => json_encode($metadataSaveObject),
            ]);
        }

        $datasetVersion = DatasetVersion::where([
            'dataset_id' => $currDataset->id,
            'version' => $versionNumber,
        ])->select(['id', 'version'])->first();

        return [
            'datasetVersionId' => $datasetVersion['id'],
            'versionNumber' => $datasetVersion['version'],
        ];
    }

    public function updateMetadataVersion(
        Dataset $currDataset,
        array $newMetadata,
        array $previousMetadata
    ): int {

        $metadataSaveObject = [
            'gwdmVersion' => Config::get('metadata.GWDM.version'),
            'metadata' => $newMetadata,
            'original_metadata' => $previousMetadata,
        ];

        $dv = DatasetVersion::where([
            'dataset_id' => $currDataset->id,
            'version' => $currDataset->lastMetadataVersionNumber()->version,
        ])->first();

        $dv->metadata = json_encode($metadataSaveObject);
        $dv->save();

        return $dv->id;
    }
}
