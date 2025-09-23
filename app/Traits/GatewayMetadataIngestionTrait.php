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
        $url = $federation->endpoint_baseurl . $federation->endpoint_datasets;
        $this->log('info', "calling REMOTE collection @ {$url}");

        $response = Http::get(
            $url,
            [
                $this->determineAuthType($federation, $gsms),
                'Accept' => 'application/json',
            ]
        );
        $this->log('info', "response from REMOTE collection: status={$response->status()}, body=" . json_encode($response->body()));

        if ($response->status() === 200) {
            return collect(json_decode($response->body(), true)['items'])->keyBy('persistentId');
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

    public function deleteLocalDatasetsNotInRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        GatewayMetadataIngestionService $gmi
    ): int {
        $this->log('info', 'testing REMOTE collection for LOCAL deletions');

        $deletedCount = 0;

        $toDelete = $localItems->keys()->diff($remoteItems->keys());

        foreach ($toDelete as $pid) {
            try {
                $this->log('info', "dataset {$pid} detected LOCALLY, but NOT in REMOTE collection - DELETING");

                $ds = Dataset::where([
                    'pid' => $pid,
                    'team_id' => $gmi->getTeam(),
                    'create_origin' => 'GMI',
                ])->first();
                $this->log('info', 'dataset for deletion ' . $ds->id);

                $dsv = DatasetVersion::where('dataset_id', $ds->id)->first();
                if ($dsv) {
                    $this->log('info', 'dataset_version for deletion ' . $dsv->id);
                    // Due to constraints, delete spatial coverage first.
                    $dsvhsc = DatasetVersionHasSpatialCoverage::where('dataset_version_id', $dsv->id)->forceDelete();
                    $dsv->forceDelete();

                    unset($dsvhsc);
                    unset($dsv);
                } else {
                    $this->log('warning', "no dataset_version found for dataset {$ds->id} - skipping deletion");
                }

                $ds->forceDelete();
                $this->log('info', "dataset {$ds->id} deleted");

                unset($ds);
                $deletedCount++;
            } catch (\Exception $e) {
                $this->log('error', 'encountered internal error: ' . json_encode($e->getMessage()));
            }
        }

        return $deletedCount;
    }

    public function createLocalDatasetsMissingFromRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        Federation $federation,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi
    ): int {
        $createdCount = 0;
        $toCreate = $remoteItems->keys()->diff($localItems->keys());
        foreach ($toCreate as $pid) {
            if (!Dataset::where([
                'pid' => $pid,
                'team_id' => $gmi->getTeam(),
                ])->exists()) {
                $data = $remoteItems[$pid];
                $response = Http::get($this->makeDatasetUrl($federation, $data), $this->determineAuthType($federation, $gms));

                $this->log('info', "attempting to call dataset @ {$pid} from REMOTE collection: 
                    status={$response->status()}, url={$this->makeDatasetUrl($federation, $data)}");

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
                        $createdCount++;
                        $this->log('info', "dataset {$pid} detected in REMOTE collection, but NOT LOCALLY - CREATED");
                    } catch (\Exception $e) {
                        $this->log('error', 'encountered internal error while CREATING local dataset from remote source: ' . json_encode($e));
                    }
                }
            } else {
                $this->log('info', "attempted to re-create a dataset that already exists @ {$pid}");
            }
        }

        return $createdCount;
    }

    public function updateLocalDatasetsChangedInRemoteCatalogue(
        Collection $localItems,
        Collection $remoteItems,
        Federation $federation,
        GoogleSecretManagerService $gms,
        GatewayMetadataIngestionService $gmi
    ): int {
        $updatedCount = 0;
        foreach ($remoteItems as $pid => $data) {
            if ($localItems->has($pid)) {
                $local = $localItems[$pid];

                $response = Http::get($this->makeDatasetUrl($federation, $data), $this->determineAuthType($federation, $gms));
                if ($response->status() === 200) {
                    $team = Team::where('id', $gmi->getTeam())->first();
                    $ds = Dataset::where([
                        'pid' => $pid,
                        'team_id' => $gmi->getTeam(),
                    ])->first();
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

                    if (version_compare($data['version'], $dv['metadata']['metadata']['required']['version'], '<>')) {
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

                            $updatedCount++;
                        }

                        $this->log('info', "dataset {$pid} detected as CHANGED in REMOTE collection - UPDATED");
                    } else {
                        $this->log('info', "nothing to update - IGNORING");
                    }
                }
            }
        }

        return $updatedCount;
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
