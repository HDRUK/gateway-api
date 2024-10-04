<?php

namespace App\Http\Traits;

use Config;

use App\Models\Dataset;
use App\Models\DatasetVersion;

trait AddMetadataVersion
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
        array $previousMetadata
    ): int {
        $versionNumber = $currDataset->lastMetadataVersionNumber()->version;
        if($incomingStatus === Dataset::STATUS_ACTIVE) {
            // Determine the last version of metadata

            if ($currDataset->status !== Dataset::STATUS_DRAFT) {
                $versionNumber = $versionNumber + 1;
            }
            $versionCode = $this->formatVersion($versionNumber);
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
            DatasetVersion::create([
                'dataset_id' => $currDataset->id,
                'metadata' => json_encode($metadataSaveObject),
                'version' => $versionNumber,
            ]);
        } else {
            // Update the existing version
            DatasetVersion::where([
                'dataset_id' => $currDataset->id,
                'version' => $versionNumber,
            ])->update([
                'metadata' => json_encode($metadataSaveObject),
            ]);
        }

        return DatasetVersion::where([
            'dataset_id' => $currDataset->id,
            'version' => $versionNumber,
        ])->first()->id;

    }
}
