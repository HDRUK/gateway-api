<?php

namespace App\Http\Traits;

use Config;
use Exception;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Models\SpatialCoverage;
use App\Jobs\TermExtraction;
use App\Jobs\LinkageExtraction;
use Illuminate\Support\Str;
use MetadataManagementController as MMC;

trait MetadataOnboard
{
    /**
     * Create new Dataset, calling translation service if necessary
     *
     * @return array
     */
    public function metadataOnboard(
        array $input,
        array $team,
        string | null $inputSchema,
        string | null $inputVersion,
        bool $elasticIndexing
    ): array {
        $isCohortDiscovery = array_key_exists('is_cohort_discovery', $input) ?
            $input['is_cohort_discovery'] : false;

        //send the payload to traser
        // - traser will return the input unchanged if the data is
        //   already in the GWDM with GWDM_CURRENT_VERSION
        // - if it is not, traser will try to work out what the metadata is
        //   and translate it into the GWDM
        // - otherwise traser will return a non-200 error

        $payload = $input['metadata'];
        $payload['extra'] = [
            'id' => 'placeholder',
            'pid' => 'placeholder',
            'datasetType' => 'Health and disease',
            'publisherId' => $team['pid'],
            'publisherName' => $team['name']
        ];

        $traserResponse = MMC::translateDataModelType(
            json_encode($payload),
            Config::get('metadata.GWDM.name'),
            Config::get('metadata.GWDM.version'),
            $inputSchema,
            $inputVersion,
            $input['status'] !== Dataset::STATUS_DRAFT, // Disable input validation if it's a draft
            $input['status'] !== Dataset::STATUS_DRAFT // Disable output validation if it's a draft
        );

        if ($traserResponse['wasTranslated']) {
            $input['metadata']['original_metadata'] = $input['metadata']['metadata'];
            $input['metadata']['metadata'] = $traserResponse['metadata'];

            $mongo_object_id = array_key_exists('mongo_object_id', $input) ? $input['mongo_object_id'] : null;
            $mongo_id = array_key_exists('mongo_id', $input) ? $input['mongo_id'] : null;
            $mongo_pid = array_key_exists('mongo_pid', $input) ? $input['mongo_pid'] : null;
            $datasetid = array_key_exists('datasetid', $input) ? $input['datasetid'] : null;

            $pid = array_key_exists('pid', $input) ? $input['pid'] : (string) Str::uuid();

            $dataset = MMC::createDataset([
                'user_id' => $input['user_id'],
                'team_id' => $input['team_id'],
                'mongo_object_id' => $mongo_object_id,
                'mongo_id' => $mongo_id,
                'mongo_pid' => $mongo_pid,
                'datasetid' => $datasetid,
                'created' => now(),
                'updated' => now(),
                'submitted' => now(),
                'pid' => $pid,
                'create_origin' => $input['create_origin'],
                'status' => $input['status'],
                'is_cohort_discovery' => $isCohortDiscovery,
            ]);

            $publisher = null;
            $revisions = [
                [
                    "url" => env('GATEWAY_URL') . '/dataset' .'/' . $dataset->id . '?version=1.0.0',
                    'version' => $this->formatVersion(1)
                ]
            ];

            $required = [
                    'gatewayId' => strval($dataset->id), //note: do we really want this in the GWDM?
                    'gatewayPid' => $dataset->pid,
                    'issued' => $dataset->created,
                    'modified' => $dataset->updated,
                    'revisions' => $revisions
                ];

            // -------------------------------------------------------------------
            // * Create a new 'required' section for the metadata to be saved
            //    - otherwise this section is filled with placeholders by all translations to GWDM
            // * Force correct publisher field based on the team associated with
            //
            // Note:
            //     - This is hopefully a rare scenario when the BE has to be changed due to an update
            //        to the GWDM
            //     - future releases of the GWDM will hopefully not modify anything that we need to
            //       set via the MMC
            //     - we can't pass the publisherId nor the gatewayPid of the dataset to traser before
            //       they have been created, this is why we are doing this..
            //     - GWDM >= 1.1 versions have a change related to these sections of the GWDM
            //         - addition of the field 'version' in the required field
            //         - restructure of the 'publisher' in the summary field
            //            - publisher.publisherId --> publisher.gatewayId
            //            - publisher.publisherName --> publisher.name
            // -------------------------------------------------------------------
            if (version_compare(Config::get('metadata.GWDM.version'), '1.1', '<')) {
                $publisher = [
                    'publisherId' => $team['pid'],
                    'publisherName' => $team['name'],
                ];
                $input['metadata']['metadata']['summary']['publisher'] = $publisher;
            } else {
                $version = $this->formatVersion(1);
                if (array_key_exists('version', $input['metadata']['metadata']['required'])) {
                    $version = $input['metadata']['metadata']['required']['version'];
                }
                $required['version'] = $version;
            }

            $input['metadata']['metadata']['required'] = $required;

            //include a note of what the metadata was (i.e. which GWDM version)
            $input['metadata']['gwdmVersion'] =  Config::get('metadata.GWDM.version');

            $version = MMC::createDatasetVersion([
                'dataset_id' => $dataset->id,
                'metadata' => json_encode($input['metadata']),
                'version' => 1,
            ]);

            // map coverage/spatial field to controlled list for filtering
            $this->mapCoverage($input['metadata'], $version);

            // Dispatch term extraction to a subprocess if the dataset is marked as active
            if ($input['status'] === Dataset::STATUS_ACTIVE) {

                LinkageExtraction::dispatch(
                    $dataset->id,
                    $version->id
                );

                if (Config::get('ted.enabled')) {
                    $tedData = Config::get('ted.use_partial') ? $input['metadata']['metadata']['summary'] : $input['metadata']['metadata'];

                    TermExtraction::dispatch(
                        $dataset->id,
                        $version->id,
                        '1',
                        base64_encode(gzcompress(gzencode(json_encode($tedData)))),
                        $elasticIndexing,
                        Config::get('ted.use_partial')
                    );
                }
            }

            return [
                'translated' => true,
                'dataset_id' => $dataset->id,
                'version_id' => $version->id,
            ];
        } else {
            return [
                'translated' => false,
                'response' => $traserResponse,
            ];
        }

    }

    private function mapCoverage(array $metadata, DatasetVersion $version): void
    {
        if (!isset($metadata['metadata']['coverage']['spatial'])) {
            return;
        }

        $coverage = strtolower($metadata['metadata']['coverage']['spatial']);
        $ukCoverages = SpatialCoverage::whereNot('region', 'Rest of the world')->get();
        $worldId = SpatialCoverage::where('region', 'Rest of the world')->first()->id;

        $matchFound = false;
        foreach ($ukCoverages as $c) {
            if (str_contains($coverage, strtolower($c['region']))) {

                DatasetVersionHasSpatialCoverage::updateOrCreate([
                    'dataset_version_id' => (int)$version['id'],
                    'spatial_coverage_id' => (int)$c['id'],
                ]);
                $matchFound = true;
            }
        }

        if (!$matchFound) {
            if (str_contains($coverage, 'united kingdom')) {
                foreach ($ukCoverages as $c) {
                    DatasetVersionHasSpatialCoverage::updateOrCreate([
                        'dataset_version_id' => (int)$version['id'],
                        'spatial_coverage_id' => (int)$c['id'],
                    ]);
                }
            } else {
                DatasetVersionHasSpatialCoverage::updateOrCreate([
                    'dataset_version_id' => (int)$version['id'],
                    'spatial_coverage_id' => (int)$worldId,
                ]);
            }
        }
    }

    public function getVersion(int $version)
    {
        if ($version > 999) {
            throw new Exception('too many versions');
        }

        $version = max(0, $version);

        $hundreds = floor($version / 100);
        $tens = floor(($version % 100) / 10);
        $units = $version % 10;

        $formattedVersion = "{$hundreds}.{$tens}.{$units}";

        return $formattedVersion;
    }

    public function formatVersion(int $version)
    {
        $formattedVersion = "{$version}.0.0";
        return $formattedVersion;
    }
}
