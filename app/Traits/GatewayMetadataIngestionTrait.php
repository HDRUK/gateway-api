<?php

namespace App\Traits;

use Http;
use Config;
use MetadataManagementController as MMC;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

use App\Models\Team;
use App\Models\Dataset;
use App\Models\Federation;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasSpatialCoverage;
use App\Http\Traits\MetadataVersioning;

use App\Services\GatewayMetadataIngestionService;
use App\Services\GoogleSecretManagerService;

trait GatewayMetadataIngestionTrait
{
    use MetadataVersioning;

    public function pullCatalogueList(Federation|array $federation, GoogleSecretManagerService $gsms): Collection
    {
        if (!is_array($federation)) {
            return $this->getCatalogueFromFederationModel($federation, $gsms);
        }

        return $this->getCatalogueFromFederationArray($federation, $gsms);
    }

    private function getCatalogueFromFederationModel(Federation $federation, GoogleSecretManagerService $gsms): Collection|array
    {
        $response = Http::get(
            $federation->endpoint_baseurl . $federation->endpoint_datasets,
            $this->determineAuthType($federation, $gsms)
        );
        if ($response->status() === 200) {
            return collect($response->json()['items'])->keyBy('persistentId');
        }

        return [
            'data' => [
                'errors' => $response->json(),
                'status' => $response->status(),
                'success' => false,
                'title' => 'Test Unsuccessful',
            ],
        ];
    }

    private function getCatalogueFromFederationArray(array $federation, GoogleSecretManagerService $gsms): Collection|array
    {
        $response = Http::get(
            $federation['endpoint_baseurl'] . $federation['endpoint_datasets'],
            $this->determineAuthType($federation, $gsms)
        );
        if ($response->status() === 200) {
            return collect($response->json()['items'])->keyBy('persistentId');
        }

        return [
            'data' => [
                'errors' => $response->json(),
                'status' => $response->status(),
                'success' => false,
                'title' => 'Test Unsuccessful',
            ],
        ];
    }

    public function getLocalDatasetsForFederatedTeam(GatewayMetadataIngestionService $gmi): Collection
    {
        return collect(Dataset::where([
            'team_id' => $gmi->getTeam(),
        ])->get())->keyBy('pid');
    }

    public function deleteLocalDatasetsNotInRemoteCatalogue(Collection $localItems, Collection $remoteItems): bool
    {
        $this->log('info', 'testing REMOTE collection for LOCAL deletions');

        $haveDeleted = false;

        $toDelete = $localItems->keys()->diff($remoteItems->keys());

        foreach ($toDelete as $pid) {
            try {
                $this->log('info', "dataset {$pid} detected LOCALLY, but NOT in REMOTE collection - DELETING");

                $ds = Dataset::where('pid', $pid)->first();
                $this->log('info', 'dataset for deletion ' . $ds->id);

                $dsv = DatasetVersion::where('dataset_id', $ds->id)->first();
                $this->log('info', 'dataset_version for deletion ' . $dsv->id);

                // Due to constraints, delete spatial coverage first.
                $dsvhsc = DatasetVersionHasSpatialCoverage::where('dataset_version_id', $dsv->id)->delete();
                $dsv->delete();
                $ds->delete();

                $this->log('info', "dataset {$ds->id}, dataset_version {$dsv->id} and associated dataset_version_has_spatial_coverage deleted");

                unset($ds);
                unset($dsv);

                $haveDeleted = true;
            } catch (\Exception $e) {
                $this->log('error', 'encountered internal error: ' . json_encode($e));
            }
        }

        return $haveDeleted;
    }

    public function createLocalDatasetsMissingFromRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        Federation $federation,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi
    ): void {
        $toCreate = $remoteItems->keys()->diff($localItems->keys());
        foreach ($toCreate as $pid) {
            if (!Dataset::where('pid', $pid)->exists()) {
                $data = $remoteItems[$pid];
                $response = Http::get($this->makeDatasetUrl($federation, $data), $this->determineAuthType($federation, $gms));
                if ($response->status() === 200) {
                    try {
                        $input = [
                            'status' => 'ACTIVE',
                            'create_origin' => 'GMI',
                            'user_id' => Config::get('metadata.system_user_id'),
                            'team_id' => $gmi->getTeam(),
                            'metadata' => [
                                'metadata' => $response->json(),
                            ],
                            'pid' => $pid,
                        ];

                        $result = $gmi->storeMetadata($input);
                        $this->log('info', "dataset {$pid} detected in REMOTE collection, but NOT LOCALLY - CREATED");
                    } catch (\Exception $e) {
                        dd($e->getMessage());
                        $this->log('error', 'encountered internal error while CREATING local dataset from remote source: ' . json_encode($e));
                    }
                }
            } else {
                $this->log('info', "attempted to re-create a dataset that already exists @ {$pid}");
            }
        }
    }

    public function updateLocalDatasetsChangedInRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        Federation $federation,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi
    ): void {
        foreach ($remoteItems as $pid => $data) {
            if ($localItems->has($pid)) {
                $local = $localItems[$pid];

                $response = Http::get($this->makeDatasetUrl($federation, $data), $this->determineAuthType($federation, $gms));
                if ($response->status() === 200) {
                    $team = Team::where('id', $gmi->getTeam())->first();
                    $ds = Dataset::where('pid', $pid)->first();
                    $dv = DatasetVersion::where('dataset_id', $local->id)->orderBy('id', 'desc')->first()->toArray();

                    $payload = [
                        'extra' => [
                            'id' => $ds->id,
                            'pid' => $ds->pid,
                            'datasetType' => 'Health and disease',
                            'publisherId' => $team->pid,
                            'publisherName' => $team->name,
                        ],
                        'metadata' => $response->json(),
                    ];

                    $this->log('info', "version compare of REMOTE v{$data['version']} and LOCAL v{$dv['metadata']['metadata']['required']['version']}");

                    if (version_compare($data['version'], $dv['metadata']['metadata']['required']['version'], '>')) {
                        $this->log('info', "found version difference in REMOTE metadata of v{$data['version']} vs local {$dv['metadata']['metadata']['required']['version']} - UPDATING LOCAL");
                        $traserResponse = MMC::translateDataModelType(
                            json_encode($payload),
                            Config::get('metadata.GWDM.name'),
                            Config::get('metadata.GWDM.version')
                        );

                        if ($traserResponse['wasTranslated']) {
                            $ds->update([
                                'updated_at' => \Carbon\Carbon::now(),
                            ]);

                            $versionNumber = $ds->lastMetadataVersionNumber()->version;
                            $dsId = $this->updateMetadataVersion(
                                $ds,
                                $traserResponse['metadata'],
                                $data,
                            );
                        }

                        $this->log('info', "dataset {$pid} detected as CHANGED in REMOTE collection - UPDATED");
                    } else {
                        $this->log('info', "nothing to update - IGNORING");
                    }
                }
            }
        }
    }

    public function determineAuthType(Federation|array $federation, GoogleSecretManagerService $gsms, bool $testMode = false): array
    {
        if (!is_array($federation) && !$testMode) {
            switch($federation->auth_type) {
                case 'BEARER':
                    $key = $gsms->getSecret($federation->auth_secret_key_location);
                    return [
                        'Authorization' => 'Bearer ' . json_decode($key, true)['bearer_token'],
                    ];
                case 'API_KEY':
                    $key = $gsms->getSecret($federation->auth_secret_key_location);
                    return [
                        'apikey' => $key,
                    ];
                case 'NO_AUTH':
                    // Nothing to do
                    return [];
                default:
                    Log::error('unknown auth_type ' . $federation->auth_type . ' - aborting');
                    return [];
            }
        } else {
            switch ($federation['auth_type']) {
                case 'BEARER':
                    return [
                        'Authorization' => 'Bearer ' . $federation['auth_secret_key'],
                    ];
                case 'API_KEY':
                    return [
                        'apikey' => $federation['auth_secret_key'],
                    ];
                case 'NO_AUTH':
                    return [];
                default:
                    Log::error('unknown auth_type ' . $federation['auth_type'] . ' - aboring');
                    return [];
            }
        }
    }

    public function makeDatasetUrl(Federation $federation, array $data): string
    {
        return $federation->endpoint_baseurl .
            str_replace('{id}', $data['persistentId'], $federation->endpoint_dataset);
    }

    public function log(string $level, string $message): void
    {
        Log::{$level}($message);
    }
}
